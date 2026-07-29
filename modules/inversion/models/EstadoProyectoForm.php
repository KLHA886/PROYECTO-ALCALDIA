<?php

declare(strict_types=1);

namespace app\modules\inversion\models;

use yii\base\Model;

final class EstadoProyectoForm extends Model
{
    private const TRANSITIONS = [
        'Borrador' => ['Presentado'],
        'Presentado' => ['En revisión', 'Rechazado'],
        'En revisión' => ['Aprobado', 'Rechazado'],
        'Aprobado' => [],
        'Rechazado' => ['En revisión'],
    ];

    public string $estado = '';
    public string $estadoActual = '';

    public function rules(): array
    {
        return [
            ['estado', 'required'],
            ['estado', 'in', 'range' => $this->allowedStates()],
        ];
    }

    /**
     * @return string[]
     */
    public function allowedStates(): array
    {
        return self::TRANSITIONS[$this->estadoActual] ?? [];
    }

    public function attributeLabels(): array
    {
        return ['estado' => 'Nuevo estado'];
    }
}
