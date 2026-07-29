<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\modules\inversion\models\ConsultaSubsanacionForm $model */
/** @var array<int, array<string, mixed>> $observations */

use app\modules\inversion\models\SubsanacionForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Subsanar expediente';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subsanacion-expediente mx-auto">
    <div class="mb-4">
        <span class="badge text-bg-warning mb-2">Corrección documental</span>
        <h1 class="h2"><?= Html::encode($this->title) ?></h1>
        <p class="text-body-secondary">
            Consulte las observaciones con el código del proyecto y el RUC del inversionista.
        </p>
    </div>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <?php $form = ActiveForm::begin(['id' => 'consulta-subsanacion-form']) ?>
            <div class="row g-3 align-items-end">
                <div class="col-md-6"><?= $form->field($model, 'codigo')->textInput(['placeholder' => 'INV-20260729-000001']) ?></div>
                <div class="col-md-4"><?= $form->field($model, 'ruc')->textInput(['maxlength' => 13, 'inputmode' => 'numeric']) ?></div>
                <div class="col-md-2 d-grid"><?= Html::submitButton('Consultar', ['class' => 'btn btn-primary mb-3']) ?></div>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </section>

    <?php foreach ($observations as $observation): ?>
        <?php $replacement = new SubsanacionForm([
            'codigo' => $model->codigo,
            'ruc' => $model->ruc,
            'observacionId' => (string) $observation['id'],
        ]) ?>
        <section class="card border-warning shadow-sm mb-3">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between gap-3 mb-2">
                    <h2 class="h5 mb-0"><?= Html::encode((string) $observation['tipo_documento']) ?></h2>
                    <span class="badge text-bg-warning">Pendiente</span>
                </div>
                <p><?= nl2br(Html::encode((string) $observation['observacion'])) ?></p>
                <div class="small text-body-secondary mb-3">
                    Archivo observado: <?= Html::encode((string) $observation['nombre_archivo']) ?>
                </div>

                <?php $uploadForm = ActiveForm::begin([
                    'action' => ['enviar'],
                    'options' => ['enctype' => 'multipart/form-data'],
                ]) ?>
                <?= Html::activeHiddenInput($replacement, 'codigo') ?>
                <?= Html::activeHiddenInput($replacement, 'ruc') ?>
                <?= Html::activeHiddenInput($replacement, 'observacionId') ?>
                <?= $uploadForm->field($replacement, 'documento')->fileInput(['accept' => 'application/pdf']) ?>
                <?= Html::submitButton('Enviar documento corregido', [
                    'class' => 'btn btn-warning',
                    'data' => ['confirm' => '¿Confirma el reemplazo del documento?'],
                ]) ?>
                <?php ActiveForm::end() ?>
            </div>
        </section>
    <?php endforeach ?>
</div>
