<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\modules\inversion\models\SolicitudForm $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Nueva solicitud de inversión';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="solicitud-inversion">
    <div class="mb-4">
        <span class="badge text-bg-primary mb-2">Ventanilla Única</span>
        <h1 class="h2 mb-2"><?= Html::encode($this->title) ?></h1>
        <p class="text-body-secondary mb-0">
            Complete la información y adjunte documentos PDF firmados. Tamaño máximo: 10 MB por archivo.
        </p>
    </div>

    <?php $form = ActiveForm::begin([
        'id' => 'solicitud-inversion-form',
        'options' => ['enctype' => 'multipart/form-data', 'novalidate' => true],
        'errorSummaryCssClass' => 'alert alert-danger',
    ]) ?>
    <?= $form->errorSummary($model, ['header' => '<strong>Revise la información ingresada:</strong>']) ?>

    <section class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">1. Datos del inversionista</h2>
            <div class="row g-3">
                <div class="col-md-6"><?= $form->field($model, 'razonSocial') ?></div>
                <div class="col-md-3"><?= $form->field($model, 'tipoPersona')->dropDownList(['Natural' => 'Natural', 'Jurídica' => 'Jurídica'], ['prompt' => 'Seleccione']) ?></div>
                <div class="col-md-3"><?= $form->field($model, 'ruc')->textInput(['maxlength' => 13, 'inputmode' => 'numeric']) ?></div>
                <div class="col-md-6"><?= $form->field($model, 'email')->input('email') ?></div>
                <div class="col-md-6"><?= $form->field($model, 'telefono')->input('tel') ?></div>
                <div class="col-md-6"><?= $form->field($model, 'direccionInversionista') ?></div>
                <div class="col-md-6"><?= $form->field($model, 'domicilioTributario') ?></div>
            </div>
        </div>
    </section>

    <section class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">2. Proyecto de inversión</h2>
            <?= $form->field($model, 'nombreProyecto') ?>
            <?= $form->field($model, 'descripcionProyecto')->textarea(['rows' => 3]) ?>
            <div class="row g-3">
                <div class="col-md-8"><?= $form->field($model, 'ubicacion')->textarea(['rows' => 2])->hint('La compatibilidad de uso de suelo será gestionada cuando corresponda.') ?></div>
                <div class="col-md-4"><?= $form->field($model, 'parroquia') ?></div>
            </div>
            <div class="row g-3">
                <div class="col-md-4"><?= $form->field($model, 'tipoFuente')->dropDownList([
                    'Recursos propios' => 'Recursos propios',
                    'Crédito bancario' => 'Crédito bancario',
                    'Inversionista' => 'Inversionista',
                    'Financiamiento público' => 'Financiamiento público',
                    'Otro' => 'Otro',
                ], ['prompt' => 'Seleccione']) ?></div>
                <div class="col-md-8"><?= $form->field($model, 'entidadFinanciera') ?></div>
            </div>
            <?= $form->field($model, 'fuentesFinanciamiento')->textarea(['rows' => 2]) ?>
            <div class="row g-3">
                <div class="col-md-8"><?= $form->field($model, 'detalleInversion')->textarea(['rows' => 3]) ?></div>
                <div class="col-md-4"><?= $form->field($model, 'montoInversion')->input('number', ['min' => '0.01', 'step' => '0.01']) ?></div>
                <div class="col-md-6"><?= $form->field($model, 'empleosProyectados')->input('number', ['min' => 0]) ?></div>
                <div class="col-md-6"><?= $form->field($model, 'empleosJovenesProyectados')->input('number', ['min' => 0]) ?></div>
                <div class="col-md-6"><?= $form->field($model, 'cargoEmpleo') ?></div>
                <div class="col-md-6"><?= $form->field($model, 'tipoContrato')->dropDownList([
                    'Indefinido' => 'Indefinido',
                    'Temporal' => 'Temporal',
                    'Servicios profesionales' => 'Servicios profesionales',
                    'Otro' => 'Otro',
                ], ['prompt' => 'Seleccione']) ?></div>
            </div>
            <?= $form->field($model, 'incluyeObra')->radioList([1 => 'Sí', 0 => 'No']) ?>
        </div>
    </section>

    <section class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <h2 class="h5 mb-1">3. Requisitos documentales</h2>
            <p class="text-body-secondary small">Solo se admiten archivos PDF.</p>
            <div class="row g-3">
                <?php foreach (\app\modules\inversion\models\SolicitudForm::DOCUMENT_ATTRIBUTES as $attribute): ?>
                    <div class="col-lg-6"><?= $form->field($model, $attribute)->fileInput(['accept' => 'application/pdf']) ?></div>
                <?php endforeach ?>
            </div>
            <div id="documentos-obra" class="border rounded-3 p-3 mt-3">
                <div class="fw-semibold mb-2">Documentos de obra (cuando corresponda)</div>
                <div class="row g-3">
                    <?php foreach (\app\modules\inversion\models\SolicitudForm::OBRA_DOCUMENT_ATTRIBUTES as $attribute): ?>
                        <div class="col-lg-6"><?= $form->field($model, $attribute)->fileInput(['accept' => 'application/pdf']) ?></div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </section>

    <section class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">4. Declaraciones</h2>
            <?= $form->field($model, 'declaracionAntilavado')->checkbox() ?>
            <?= $form->field($model, 'documentacionFirmada')->checkbox() ?>
        </div>
    </section>

    <div class="d-flex justify-content-end">
        <?= Html::submitButton('Registrar solicitud', ['class' => 'btn btn-primary btn-lg px-4']) ?>
    </div>
    <?php ActiveForm::end() ?>
</div>
