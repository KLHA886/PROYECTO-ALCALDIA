<?php

declare(strict_types=1);

namespace app\modules\inversion\models;

use yii\base\Model;
use yii\web\UploadedFile;

final class SubsanacionForm extends Model
{
    public string $codigo = '';
    public string $ruc = '';
    public string $observacionId = '';
    public ?UploadedFile $documento = null;

    public function rules(): array
    {
        return [
            [['codigo', 'ruc', 'observacionId', 'documento'], 'required'],
            [['codigo', 'ruc'], 'trim'],
            ['codigo', 'match', 'pattern' => '/^INV-\d{8}-\d{6,12}$/'],
            ['ruc', 'match', 'pattern' => '/^\d{13}$/'],
            ['observacionId', 'integer', 'min' => 1],
            [
                'documento',
                'file',
                'extensions' => ['pdf'],
                'mimeTypes' => ['application/pdf'],
                'checkExtensionByMimeType' => true,
                'maxSize' => 10 * 1024 * 1024,
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'codigo' => 'Código del expediente',
            'ruc' => 'RUC del inversionista',
            'observacionId' => 'Observación a subsanar',
            'documento' => 'Nuevo documento PDF',
        ];
    }

    public function loadFile(): void
    {
        $this->documento = UploadedFile::getInstance($this, 'documento');
    }
}
