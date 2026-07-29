<?php

declare(strict_types=1);

namespace app\tests\Unit\Modules\Inversion;

use app\modules\inversion\models\EstadoProyectoForm;
use Codeception\Test\Unit;

final class EstadoProyectoFormTest extends Unit
{
    public function testValidTransitionFromPresentedToReview(): void
    {
        $model = new EstadoProyectoForm(['estadoActual' => 'Presentado', 'estado' => 'En revisión']);

        self::assertTrue($model->validate());
    }

    public function testCannotApprovePresentedProjectDirectly(): void
    {
        $model = new EstadoProyectoForm(['estadoActual' => 'Presentado', 'estado' => 'Aprobado']);

        self::assertFalse($model->validate());
        self::assertTrue($model->hasErrors('estado'));
    }

    public function testApprovedProjectIsTerminal(): void
    {
        $model = new EstadoProyectoForm(['estadoActual' => 'Aprobado']);

        self::assertSame([], $model->allowedStates());
    }
}
