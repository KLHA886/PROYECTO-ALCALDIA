<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $codigo */

use yii\helpers\Html;

$this->title = 'Solicitud registrada';
?>
<div class="solicitud-confirmacion d-flex justify-content-center py-5">
    <div class="card border-0 shadow-sm text-center p-4 p-md-5">
        <div class="confirmacion-icon mx-auto mb-3" aria-hidden="true">✓</div>
        <h1 class="h3">Solicitud registrada</h1>
        <p class="text-body-secondary">Conserve este código para el seguimiento:</p>
        <div class="h4 font-monospace bg-body-tertiary rounded p-3"><?= Html::encode($codigo) ?></div>
        <?= Html::a('Registrar otra solicitud', ['crear'], ['class' => 'btn btn-outline-primary mt-3']) ?>
    </div>
</div>
