<?php

declare(strict_types=1);

namespace app\modules\inversion\controllers;

use app\models\User;
use app\modules\inversion\models\EstadoProyectoForm;
use app\modules\inversion\models\ObservacionDocumentoForm;
use app\modules\inversion\models\ProyectoSearch;
use app\modules\inversion\services\ProyectoHistory;
use app\modules\inversion\services\ProyectoNotifier;
use app\modules\inversion\services\SecurityAudit;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class AdministracionController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => static function (): Response {
                    if (Yii::$app->user->isGuest) {
                        return Yii::$app->user->loginRequired();
                    }
                    (new SecurityAudit())->record(
                        'Acceso administrativo',
                        'panel',
                        null,
                        false,
                        'Acceso denegado por permisos.',
                    );
                    throw new \yii\web\ForbiddenHttpException('No tiene permisos para acceder al panel.');
                },
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static function (): bool {
                            $identity = Yii::$app->user->identity;
                            return $identity instanceof User
                                && $identity->can(User::PERMISSION_REVIEW_PROJECTS);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'estado' => ['post'],
                    'observacion' => ['post'],
                    'descargar' => ['get'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $search = new ProyectoSearch();
        $search->load($this->request->get());
        $search->validate();

        $query = (new Query())
            ->select([
                'p.id',
                'p.nombre',
                'p.monto_total',
                'p.fecha_presentacion',
                'p.estado',
                'i.nombres AS inversionista',
                'i.identificacion',
            ])
            ->from(['p' => 'proyecto'])
            ->innerJoin(['i' => 'inversionista'], 'i.id = p.inversionista_id')
            ->andFilterWhere(['p.estado' => $search->estado])
            ->andFilterWhere([
                'or',
                ['like', 'p.nombre', $search->texto],
                ['like', 'i.nombres', $search->texto],
                ['like', 'i.identificacion', $search->texto],
            ]);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['fecha_presentacion' => SORT_DESC, 'id' => SORT_DESC],
                'attributes' => ['id', 'nombre', 'monto_total', 'fecha_presentacion', 'estado', 'inversionista'],
            ],
        ]);

        return $this->render('index', ['search' => $search, 'provider' => $provider]);
    }

    public function actionVer(int $id): string
    {
        $project = $this->findProject($id);
        (new SecurityAudit())->record('Consulta de expediente', 'proyecto', $id, true);
        $relations = [
            'fuentes' => (new Query())->from('fuente_financiamiento')->where(['proyecto_id' => $id])->all(),
            'inversiones' => (new Query())->from('inversion_proyectada')->where(['proyecto_id' => $id])->all(),
            'empleos' => (new Query())->from('empleo_proyectado')->where(['proyecto_id' => $id])->all(),
            'cronograma' => (new Query())->from('cronograma_obra')->where(['proyecto_id' => $id])->all(),
            'documentos' => (new Query())->from('documento')->where(['proyecto_id' => $id])->orderBy(['fecha_subida' => SORT_ASC])->all(),
            'observaciones' => (new Query())
                ->select(['o.*', 'd.tipo_documento'])
                ->from(['o' => 'observacion_documento'])
                ->innerJoin(['d' => 'documento'], 'd.id = o.documento_id')
                ->where(['d.proyecto_id' => $id])
                ->orderBy(['o.fecha_creacion' => SORT_DESC])
                ->all(),
            'historial' => (new Query())
                ->from('historial_proyecto')
                ->where(['proyecto_id' => $id])
                ->orderBy(['fecha' => SORT_DESC, 'id' => SORT_DESC])
                ->all(),
            'notificaciones' => (new Query())
                ->from('notificacion')
                ->where(['proyecto_id' => $id])
                ->orderBy(['fecha_creacion' => SORT_DESC])
                ->limit(10)
                ->all(),
        ];
        $statusForm = new EstadoProyectoForm(['estadoActual' => (string) $project['estado']]);
        $observationForm = new ObservacionDocumentoForm();

        return $this->render('ver', [
            'project' => $project,
            'relations' => $relations,
            'statusForm' => $statusForm,
            'observationForm' => $observationForm,
        ]);
    }

    public function actionObservacion(int $id): Response
    {
        $this->requirePermission(User::PERMISSION_UPDATE_PROJECT_STATUS);
        $project = $this->findProject($id);
        $model = new ObservacionDocumentoForm();

        if (
            !in_array($project['estado'], ['En revisión', 'Subsanación'], true)
            || !$model->load($this->request->post())
            || !$model->validate()
        ) {
            Yii::$app->session->setFlash(
                'error',
                'Solo puede observar documentos de proyectos que estén en revisión.',
            );
            return $this->redirect(['ver', 'id' => $id]);
        }

        $documentExists = (new Query())->from('documento')->where([
            'id' => (int) $model->documentoId,
            'proyecto_id' => $id,
        ])->exists();
        if (!$documentExists) {
            throw new \yii\web\BadRequestHttpException('El documento no pertenece al proyecto.');
        }

        Yii::$app->db->transaction(static function () use ($id, $model, $project): void {
            Yii::$app->db->createCommand()->insert('observacion_documento', [
                'documento_id' => $model->documentoId,
                'autor' => Yii::$app->user->identity?->username ?? 'Revisor',
                'observacion' => $model->observacion,
            ])->execute();
            Yii::$app->db->createCommand()
                ->update('proyecto', ['estado' => 'Subsanación'], ['id' => $id])
                ->execute();
            (new ProyectoHistory())->record(
                $id,
                Yii::$app->user->identity?->username ?? 'Revisor',
                'Observación documental',
                (string) $project['estado'],
                'Subsanación',
                $model->observacion,
            );
        });
        (new ProyectoNotifier())->notify(
            $id,
            (string) $project['correo'],
            'Su expediente requiere subsanación',
            'Se registró una observación en uno de sus documentos: ' . $model->observacion,
        );
        (new SecurityAudit())->record('Observación documental', 'proyecto', $id, true);
        Yii::$app->session->setFlash('success', 'Observación enviada al inversionista.');

        return $this->redirect(['ver', 'id' => $id]);
    }

    public function actionEstado(int $id): Response
    {
        $this->requirePermission(User::PERMISSION_UPDATE_PROJECT_STATUS);
        $project = $this->findProject($id);
        $model = new EstadoProyectoForm(['estadoActual' => (string) $project['estado']]);

        if (!$model->load($this->request->post()) || !$model->validate()) {
            Yii::$app->session->setFlash('error', 'La transición de estado solicitada no es válida.');
            return $this->redirect(['ver', 'id' => $id]);
        }

        Yii::$app->db->transaction(static function () use ($id, $model, $project): void {
            Yii::$app->db->createCommand()
                ->update('proyecto', ['estado' => $model->estado], ['id' => $id])
                ->execute();
            (new ProyectoHistory())->record(
                $id,
                Yii::$app->user->identity?->username ?? 'Revisor',
                'Cambio de estado',
                (string) $project['estado'],
                $model->estado,
            );
        });
        (new ProyectoNotifier())->notify(
            $id,
            (string) $project['correo'],
            'Estado actualizado: ' . $model->estado,
            'El proyecto "' . $project['nombre'] . '" cambió de '
                . $project['estado'] . ' a ' . $model->estado . '.',
        );
        (new SecurityAudit())->record('Cambio de estado', 'proyecto', $id, true, $model->estado);
        Yii::$app->session->setFlash('success', 'El estado del proyecto fue actualizado.');

        return $this->redirect(['ver', 'id' => $id]);
    }

    public function actionDescargar(int $id): Response
    {
        $this->requirePermission(User::PERMISSION_DOWNLOAD_DOCUMENTS);
        $document = (new Query())->from('documento')->where(['id' => $id])->one();
        if ($document === false) {
            (new SecurityAudit())->record('Descarga documental', 'documento', $id, false, 'Documento inexistente.');
            throw new NotFoundHttpException('Documento no encontrado.');
        }

        $path = realpath((string) $document['ruta_archivo']);
        $storageRoot = realpath(Yii::getAlias('@runtime/solicitudes'));
        if (
            $path === false
            || $storageRoot === false
            || !is_file($path)
            || !str_starts_with($path, $storageRoot . DIRECTORY_SEPARATOR)
        ) {
            (new SecurityAudit())->record('Descarga documental', 'documento', $id, false, 'Ruta no disponible.');
            throw new NotFoundHttpException('El archivo solicitado no está disponible.');
        }

        (new SecurityAudit())->record('Descarga documental', 'documento', $id, true);
        return Yii::$app->response->sendFile($path, (string) $document['nombre_archivo'], ['inline' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function findProject(int $id): array
    {
        $project = (new Query())
            ->select(['p.*', 'i.nombres AS inversionista', 'i.identificacion', 'i.correo', 'i.telefono', 'i.domicilio_tributario'])
            ->from(['p' => 'proyecto'])
            ->innerJoin(['i' => 'inversionista'], 'i.id = p.inversionista_id')
            ->where(['p.id' => $id])
            ->one();

        if ($project === false) {
            throw new NotFoundHttpException('Proyecto no encontrado.');
        }

        return $project;
    }

    private function requirePermission(string $permission): void
    {
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof User || !$identity->can($permission)) {
            throw new \yii\web\ForbiddenHttpException('No tiene permisos para realizar esta acción.');
        }
    }
}
