<?php

declare(strict_types=1);

namespace app\modules\inversion\controllers;

use app\modules\inversion\models\ConsultaSubsanacionForm;
use app\modules\inversion\models\SubsanacionForm;
use app\modules\inversion\services\DocumentoSubsanacionService;
use app\modules\inversion\services\LookupRateLimiter;
use app\modules\inversion\services\SecurityAudit;
use Yii;
use yii\db\Query;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

final class SubsanacionController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['index' => ['get', 'post'], 'enviar' => ['post']],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $model = new ConsultaSubsanacionForm();
        $observations = [];

        if ($model->load($this->request->post()) && $model->validate()) {
            $limiter = new LookupRateLimiter();
            if ($limiter->tooManyAttempts()) {
                (new SecurityAudit())->record(
                    'Consulta de subsanación',
                    'proyecto',
                    null,
                    false,
                    'Límite de intentos excedido.',
                    'Inversionista',
                );
                throw new TooManyRequestsHttpException(
                    'Demasiados intentos. Espere 10 minutos antes de volver a consultar.',
                );
            }
            $projectId = $this->projectIdFromCode($model->codigo);
            $observations = (new Query())
                ->select([
                    'o.id',
                    'o.observacion',
                    'o.fecha_creacion',
                    'd.tipo_documento',
                    'd.nombre_archivo',
                    'p.id AS proyecto_id',
                    'p.fecha_presentacion',
                ])
                ->from(['o' => 'observacion_documento'])
                ->innerJoin(['d' => 'documento'], 'd.id = o.documento_id')
                ->innerJoin(['p' => 'proyecto'], 'p.id = d.proyecto_id')
                ->innerJoin(['i' => 'inversionista'], 'i.id = p.inversionista_id')
                ->where([
                    'p.id' => $projectId,
                    'i.identificacion' => $model->ruc,
                    'o.estado' => 'Pendiente',
                ])
                ->all();

            $expectedCode = $observations === [] ? '' : sprintf(
                'INV-%s-%06d',
                date('Ymd', strtotime((string) $observations[0]['fecha_presentacion'])),
                (int) $observations[0]['proyecto_id'],
            );
            if (!hash_equals($expectedCode, $model->codigo)) {
                $observations = [];
            }

            if ($observations === []) {
                $limiter->hit();
                (new SecurityAudit())->record(
                    'Consulta de subsanación',
                    'proyecto',
                    $projectId > 0 ? $projectId : null,
                    false,
                    'Credenciales de expediente sin coincidencia.',
                    'Inversionista',
                );
                $model->addError('codigo', 'No existen observaciones pendientes para los datos ingresados.');
            } else {
                $limiter->clear();
                (new SecurityAudit())->record(
                    'Consulta de subsanación',
                    'proyecto',
                    $projectId,
                    true,
                    null,
                    'Inversionista',
                );
            }
        }

        return $this->render('index', [
            'model' => $model,
            'observations' => $observations,
        ]);
    }

    public function actionEnviar(): Response
    {
        $model = new SubsanacionForm();
        $model->load($this->request->post());
        $model->loadFile();
        if (!$model->validate()) {
            Yii::$app->session->setFlash('error', 'Revise el PDF y los datos del expediente.');
            return $this->redirect(['index']);
        }

        $record = $this->findPendingObservation(
            (int) $model->observacionId,
            $this->projectIdFromCode($model->codigo),
            $model->ruc,
            $model->codigo,
        );
        if ($record === false) {
            throw new BadRequestHttpException('La observación no corresponde al expediente indicado.');
        }

        (new DocumentoSubsanacionService())->replace($model, $record);
        (new SecurityAudit())->record(
            'Documento subsanado',
            'proyecto',
            (int) $record['proyecto_id'],
            true,
            null,
            'Inversionista',
        );
        Yii::$app->session->setFlash(
            'success',
            'Documento reemplazado. El expediente regresó a revisión.',
        );

        return $this->redirect(['index']);
    }

    private function projectIdFromCode(string $code): int
    {
        $parts = explode('-', $code);
        return isset($parts[2]) ? (int) $parts[2] : 0;
    }

    /**
     * @return array<string, mixed>|false
     */
    private function findPendingObservation(
        int $observationId,
        int $projectId,
        string $ruc,
        string $code,
    ): array|false {
        $record = (new Query())
            ->select([
                'o.id AS observacion_id',
                'd.id AS documento_id',
                'd.nombre_archivo',
                'd.ruta_archivo',
                'd.fecha_subida',
                'p.id AS proyecto_id',
                'p.fecha_presentacion',
                'i.correo',
            ])
            ->from(['o' => 'observacion_documento'])
            ->innerJoin(['d' => 'documento'], 'd.id = o.documento_id')
            ->innerJoin(['p' => 'proyecto'], 'p.id = d.proyecto_id')
            ->innerJoin(['i' => 'inversionista'], 'i.id = p.inversionista_id')
            ->where([
                'o.id' => $observationId,
                'o.estado' => 'Pendiente',
                'p.id' => $projectId,
                'i.identificacion' => $ruc,
            ])
            ->one();

        if ($record === false) {
            return false;
        }

        $expectedCode = sprintf(
            'INV-%s-%06d',
            date('Ymd', strtotime((string) $record['fecha_presentacion'])),
            $projectId,
        );

        return hash_equals($expectedCode, $code) ? $record : false;
    }
}
