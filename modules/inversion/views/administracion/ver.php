<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string, mixed> $project */
/** @var array<string, array<int, array<string, mixed>>> $relations */
/** @var app\modules\inversion\models\EstadoProyectoForm $statusForm */
/** @var app\modules\inversion\models\ObservacionDocumentoForm $observationForm */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = (string) $project['nombre'];
$this->params['breadcrumbs'][] = ['label' => 'Administración', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="expediente-proyecto">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div>
            <div class="text-body-secondary small">Proyecto #<?= Html::encode((string) $project['id']) ?></div>
            <h1 class="h2 mb-1"><?= Html::encode($this->title) ?></h1>
            <span class="badge text-bg-secondary"><?= Html::encode((string) $project['estado']) ?></span>
        </div>
        <?= Html::a('Volver al listado', ['index'], ['class' => 'btn btn-outline-secondary align-self-start']) ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5">Información del proyecto</h2>
                    <p><?= nl2br(Html::encode((string) $project['descripcion'])) ?></p>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Ubicación</dt><dd class="col-sm-8"><?= Html::encode((string) $project['direccion_proyecto']) ?></dd>
                        <dt class="col-sm-4">Parroquia</dt><dd class="col-sm-8"><?= Html::encode((string) $project['parroquia']) ?></dd>
                        <dt class="col-sm-4">Monto total</dt><dd class="col-sm-8"><?= Yii::$app->formatter->asCurrency($project['monto_total'], 'USD') ?></dd>
                        <dt class="col-sm-4">Requiere obra</dt><dd class="col-sm-8"><?= $project['requiere_obra'] ? 'Sí' : 'No' ?></dd>
                    </dl>
                </div>
            </section>

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5">Documentos</h2>
                    <?php if ($relations['documentos'] === []): ?>
                        <p class="text-body-secondary mb-0">No hay documentos registrados.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($relations['documentos'] as $document): ?>
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold"><?= Html::encode((string) $document['tipo_documento']) ?></div>
                                        <small class="text-body-secondary"><?= Html::encode((string) $document['nombre_archivo']) ?></small>
                                    </div>
                                    <?= Html::a('Descargar', ['descargar', 'id' => $document['id']], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </section>

            <section class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5">Proyección</h2>
                    <?php foreach ($relations['empleos'] as $employment): ?>
                        <p class="mb-2">
                            <strong><?= Html::encode((string) $employment['cargo']) ?>:</strong>
                            <?= Html::encode((string) $employment['cantidad_personas']) ?> empleos,
                            <?= Html::encode((string) $employment['cantidad_jovenes']) ?> jóvenes —
                            <?= Html::encode((string) $employment['tipo_contrato']) ?>
                        </p>
                    <?php endforeach ?>
                    <?php foreach ($relations['fuentes'] as $source): ?>
                        <p class="mb-0">
                            <strong>Financiamiento:</strong> <?= Html::encode((string) $source['tipo_fuente']) ?>,
                            <?= Yii::$app->formatter->asCurrency($source['monto'], 'USD') ?>
                        </p>
                    <?php endforeach ?>
                </div>
            </section>

            <?php if ($relations['observaciones'] !== []): ?>
                <section class="card border-0 shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h2 class="h5">Historial de observaciones</h2>
                        <?php foreach ($relations['observaciones'] as $observation): ?>
                            <div class="border-start border-3 ps-3 mb-3">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong><?= Html::encode((string) $observation['tipo_documento']) ?></strong>
                                    <span class="badge text-bg-secondary"><?= Html::encode((string) $observation['estado']) ?></span>
                                </div>
                                <p class="mb-1"><?= nl2br(Html::encode((string) $observation['observacion'])) ?></p>
                                <small class="text-body-secondary">
                                    <?= Html::encode((string) $observation['autor']) ?> ·
                                    <?= Yii::$app->formatter->asDatetime($observation['fecha_creacion']) ?>
                                </small>
                            </div>
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endif ?>

            <section class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h2 class="h5">Trazabilidad del expediente</h2>
                    <?php if ($relations['historial'] === []): ?>
                        <p class="text-body-secondary mb-0">Aún no existen eventos registrados.</p>
                    <?php else: ?>
                        <div class="audit-timeline">
                            <?php foreach ($relations['historial'] as $event): ?>
                                <div class="audit-event pb-3">
                                    <div class="fw-semibold"><?= Html::encode((string) $event['accion']) ?></div>
                                    <?php if ($event['estado_anterior'] !== null || $event['estado_nuevo'] !== null): ?>
                                        <div class="small">
                                            <?= Html::encode((string) $event['estado_anterior']) ?>
                                            → <?= Html::encode((string) $event['estado_nuevo']) ?>
                                        </div>
                                    <?php endif ?>
                                    <?php if ($event['detalle'] !== null): ?>
                                        <div><?= Html::encode((string) $event['detalle']) ?></div>
                                    <?php endif ?>
                                    <small class="text-body-secondary">
                                        <?= Html::encode((string) $event['actor']) ?> ·
                                        <?= Yii::$app->formatter->asDatetime($event['fecha']) ?>
                                    </small>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </section>
        </div>

        <aside class="col-lg-4">
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5">Inversionista</h2>
                    <div class="fw-semibold"><?= Html::encode((string) $project['inversionista']) ?></div>
                    <div>RUC: <?= Html::encode((string) $project['identificacion']) ?></div>
                    <div><?= Html::encode((string) $project['correo']) ?></div>
                    <div><?= Html::encode((string) $project['telefono']) ?></div>
                    <div class="text-body-secondary small mt-2"><?= Html::encode((string) $project['domicilio_tributario']) ?></div>
                </div>
            </section>

            <section class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5">Actualizar estado</h2>
                    <?php if ($statusForm->allowedStates() === []): ?>
                        <p class="text-body-secondary mb-0">Este expediente no tiene transiciones disponibles.</p>
                    <?php else: ?>
                        <?php $form = ActiveForm::begin(['action' => ['estado', 'id' => $project['id']]]) ?>
                        <?= $form->field($statusForm, 'estado')->dropDownList(
                            array_combine($statusForm->allowedStates(), $statusForm->allowedStates()),
                            ['prompt' => 'Seleccione'],
                        ) ?>
                        <?= Html::submitButton('Actualizar', [
                            'class' => 'btn btn-primary w-100',
                            'data' => ['confirm' => '¿Confirma el cambio de estado?'],
                        ]) ?>
                        <?php ActiveForm::end() ?>
                    <?php endif ?>
                </div>
            </section>

            <?php if (in_array($project['estado'], ['En revisión', 'Subsanación'], true) && $relations['documentos'] !== []): ?>
                <section class="card border-0 shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h2 class="h5">Solicitar subsanación</h2>
                        <?php $form = ActiveForm::begin(['action' => ['observacion', 'id' => $project['id']]]) ?>
                        <?= $form->field($observationForm, 'documentoId')->dropDownList(
                            array_column($relations['documentos'], 'tipo_documento', 'id'),
                            ['prompt' => 'Seleccione un documento'],
                        ) ?>
                        <?= $form->field($observationForm, 'observacion')->textarea(['rows' => 4]) ?>
                        <?= Html::submitButton('Enviar observación', [
                            'class' => 'btn btn-warning w-100',
                            'data' => ['confirm' => 'El expediente pasará a subsanación. ¿Desea continuar?'],
                        ]) ?>
                        <?php ActiveForm::end() ?>
                    </div>
                </section>
            <?php endif ?>

            <section class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h2 class="h5">Notificaciones</h2>
                    <?php if ($relations['notificaciones'] === []): ?>
                        <p class="text-body-secondary mb-0">No se han generado notificaciones.</p>
                    <?php else: ?>
                        <?php foreach ($relations['notificaciones'] as $notification): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <div class="small fw-semibold"><?= Html::encode((string) $notification['asunto']) ?></div>
                                <div class="d-flex justify-content-between small text-body-secondary">
                                    <span><?= Html::encode((string) $notification['destinatario']) ?></span>
                                    <span><?= Html::encode((string) $notification['estado']) ?></span>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </section>
        </aside>
    </div>
</div>
