<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\modules\inversion\models\ProyectoSearch $search */
/** @var yii\data\ActiveDataProvider $provider */

use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Administración de proyectos';
$this->params['breadcrumbs'][] = $this->title;
$states = ['Borrador', 'Presentado', 'En revisión', 'Subsanación', 'Aprobado', 'Rechazado'];
?>
<div class="administracion-proyectos">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h2 mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-body-secondary mb-0">Revise y gestione los expedientes presentados.</p>
        </div>
        <?= Html::a('Nueva solicitud', ['/inversion/solicitud/crear'], ['class' => 'btn btn-primary']) ?>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index']]) ?>
            <div class="row g-3 align-items-end">
                <div class="col-md-7"><?= $form->field($search, 'texto')->textInput(['placeholder' => 'Proyecto, inversionista o RUC']) ?></div>
                <div class="col-md-3"><?= $form->field($search, 'estado')->dropDownList(array_combine($states, $states), ['prompt' => 'Todos']) ?></div>
                <div class="col-md-2 d-grid"><?= Html::submitButton('Filtrar', ['class' => 'btn btn-outline-primary mb-3']) ?></div>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <?= GridView::widget([
            'dataProvider' => $provider,
            'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
            'summaryOptions' => ['class' => 'px-3 py-2 text-body-secondary small'],
            'emptyText' => 'No existen proyectos con los criterios seleccionados.',
            'columns' => [
                ['attribute' => 'id', 'label' => '#'],
                ['attribute' => 'nombre', 'label' => 'Proyecto'],
                ['attribute' => 'inversionista', 'label' => 'Inversionista'],
                ['attribute' => 'identificacion', 'label' => 'RUC'],
                [
                    'attribute' => 'monto_total',
                    'label' => 'Inversión',
                    'format' => ['currency', 'USD'],
                ],
                ['attribute' => 'fecha_presentacion', 'label' => 'Presentado', 'format' => 'date'],
                [
                    'attribute' => 'estado',
                    'format' => 'raw',
                    'value' => static fn (array $row): string => Html::tag('span', Html::encode($row['estado']), [
                        'class' => 'badge rounded-pill text-bg-secondary',
                    ]),
                ],
                [
                    'format' => 'raw',
                    'value' => static fn (array $row): string => Html::a('Revisar', ['ver', 'id' => $row['id']], [
                        'class' => 'btn btn-sm btn-outline-primary',
                        'aria-label' => 'Revisar ' . $row['nombre'],
                    ]),
                ],
            ],
            ]) ?>
        </div>
    </div>
</div>
