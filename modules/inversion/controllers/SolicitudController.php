<?php

declare(strict_types=1);

namespace app\modules\inversion\controllers;

use app\modules\inversion\models\SolicitudForm;
use app\modules\inversion\services\SolicitudStorage;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class SolicitudController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['crear' => ['get', 'post']],
            ],
        ];
    }

    public function actionCrear(): Response|string
    {
        $model = new SolicitudForm();

        if ($model->load($this->request->post())) {
            $model->loadUploadedFiles();
            if ($model->validate()) {
                $code = (new SolicitudStorage())->save($model);
                Yii::$app->session->setFlash('success', 'La solicitud fue registrada correctamente.');

                return $this->redirect(['confirmacion', 'codigo' => $code]);
            }
        }

        return $this->render('crear', ['model' => $model]);
    }

    public function actionConfirmacion(string $codigo): string
    {
        if (!preg_match('/^INV-\d{8}-\d{6,12}$/', $codigo)) {
            throw new \yii\web\BadRequestHttpException('Código de solicitud inválido.');
        }

        return $this->render('confirmacion', ['codigo' => $codigo]);
    }
}
