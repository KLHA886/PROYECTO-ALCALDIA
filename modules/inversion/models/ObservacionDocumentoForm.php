<?php

declare(strict_types=1);

namespace app\modules\inversion\models;

use yii\base\Model;

final class ObservacionDocumentoForm extends Model
{
    public string $documentoId = '';
    public string $observacion = '';

    public function rules(): array
    {
        return [
            [['documentoId', 'observacion'], 'required'],
            ['documentoId', 'integer', 'min' => 1],
            ['observacion', 'trim'],
            ['observacion', 'string', 'min' => 10, 'max' => 2000],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'documentoId' => 'Documento observado',
            'observacion' => 'Detalle de la observación',
        ];
    }
}
