<?php

declare(strict_types=1);

namespace app\modules\inversion\services;

use Yii;
use yii\db\Connection;

final class ProyectoHistory
{
    public function __construct(private readonly ?Connection $connection = null)
    {
    }

    public function record(
        int $projectId,
        string $actor,
        string $action,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?string $detail = null,
    ): void {
        ($this->connection ?? Yii::$app->db)->createCommand()->insert('historial_proyecto', [
            'proyecto_id' => $projectId,
            'actor' => $actor,
            'accion' => $action,
            'estado_anterior' => $previousStatus,
            'estado_nuevo' => $newStatus,
            'detalle' => $detail,
        ])->execute();
    }
}
