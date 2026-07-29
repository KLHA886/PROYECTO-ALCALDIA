<?php

declare(strict_types=1);

namespace app\modules\inversion\models;

use yii\base\Model;

final class ProyectoSearch extends Model
{
    public string $texto = '';
    public string $estado = '';

    public function rules(): array
    {
        return [
            [['texto', 'estado'], 'trim'],
            ['texto', 'string', 'max' => 200],
            ['estado', 'in', 'range' => ['Borrador', 'Presentado', 'En revisión', 'Aprobado', 'Rechazado']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'texto' => 'Buscar',
            'estado' => 'Estado',
        ];
    }
}
