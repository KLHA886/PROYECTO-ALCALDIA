<?php

declare(strict_types=1);

namespace app\modules\inversion\models;

use yii\base\Model;

final class ConsultaSubsanacionForm extends Model
{
    public string $codigo = '';
    public string $ruc = '';

    public function rules(): array
    {
        return [
            [['codigo', 'ruc'], 'required'],
            [['codigo', 'ruc'], 'trim'],
            ['codigo', 'match', 'pattern' => '/^INV-\d{8}-\d{6,12}$/'],
            ['ruc', 'match', 'pattern' => '/^\d{13}$/'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'codigo' => 'Código del expediente',
            'ruc' => 'RUC del inversionista',
        ];
    }
}
