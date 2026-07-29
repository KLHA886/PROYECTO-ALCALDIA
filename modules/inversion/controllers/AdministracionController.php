<?php

declare(strict_types=1);

namespace app\modules\inversion\controllers;

use app\modules\inversion\models\EstadoProyectoForm;
use app\modules\inversion\models\ProyectoSearch;
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
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static fn (): bool => Yii::$app->user->identity?->username === 'admin',
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['estado' => ['post'], 'descargar' => ['get']],
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
        $relations = [
            'fuentes' => (new Query())->from('fuente_financiamiento')->where(['proyecto_id' => $id])->all(),
            'inversiones' => (new Query())->from('inversion_proyectada')->where(['proyecto_id' => $id])->all(),
            'empleos' => (new Query())->from('empleo_proyectado')->where(['proyecto_id' => $id])->all(),
            'cronograma' => (new Query())->from('cronograma_obra')->where(['proyecto_id' => $id])->all(),
            'documentos' => (new Query())->from('documento')->where(['proyecto_id' => $id])->orderBy(['fecha_subida' => SORT_ASC])->all(),
        ];
        $statusForm = new EstadoProyectoForm(['estadoActual' => (string) $project['estado']]);

        return $this->render('ver', [
            'project' => $project,
            'relations' => $relations,
            'statusForm' => $statusForm,
        ]);
    }

    public function actionEstado(int $id): Response
    {
        $project = $this->findProject($id);
        $model = new EstadoProyectoForm(['estadoActual' => (string) $project['estado']]);

        if (!$model->load($this->request->post()) || !$model->validate()) {
            Yii::$app->session->setFlash('error', 'La transición de estado solicitada no es válida.');
            return $this->redirect(['ver', 'id' => $id]);
        }

        Yii::$app->db->createCommand()
            ->update('proyecto', ['estado' => $model->estado], ['id' => $id])
            ->execute();
        Yii::$app->session->setFlash('success', 'El estado del proyecto fue actualizado.');

        return $this->redirect(['ver', 'id' => $id]);
    }

    public function actionDescargar(int $id): Response
    {
        $document = (new Query())->from('documento')->where(['id' => $id])->one();
        if ($document === false) {
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
            throw new NotFoundHttpException('El archivo solicitado no está disponible.');
        }

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
}
