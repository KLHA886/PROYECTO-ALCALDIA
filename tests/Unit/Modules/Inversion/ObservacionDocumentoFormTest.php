<?php

declare(strict_types=1);

namespace app\tests\Unit\Modules\Inversion;

use app\modules\inversion\models\ObservacionDocumentoForm;
use Codeception\Test\Unit;

final class ObservacionDocumentoFormTest extends Unit
{
    public function testObservationNeedsEnoughDetail(): void
    {
        $model = new ObservacionDocumentoForm(['documentoId' => '2', 'observacion' => 'Corta']);

        self::assertFalse($model->validate());
        self::assertTrue($model->hasErrors('observacion'));
    }

    public function testDetailedObservationIsValid(): void
    {
        $model = new ObservacionDocumentoForm([
            'documentoId' => '2',
            'observacion' => 'La firma de la última página no es legible.',
        ]);

        self::assertTrue($model->validate());
    }
}
