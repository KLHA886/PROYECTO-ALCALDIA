<?php

declare(strict_types=1);

namespace app\tests\Unit\Modules\Inversion;

use app\modules\inversion\models\SolicitudForm;
use Codeception\Test\Unit;
use yii\web\UploadedFile;

final class SolicitudFormTest extends Unit
{
    public function testEmptyFormIsInvalid(): void
    {
        $model = new SolicitudForm();

        self::assertFalse($model->validate());
        self::assertTrue($model->hasErrors('ruc'));
        self::assertTrue($model->hasErrors('peticionFormal'));
        self::assertTrue($model->hasErrors('declaracionAntilavado'));
    }

    public function testRucMustHaveThirteenDigits(): void
    {
        $model = $this->validModel();
        $model->ruc = '123';

        self::assertFalse($model->validate());
        self::assertTrue($model->hasErrors('ruc'));
    }

    public function testYoungEmploymentCannotExceedTotal(): void
    {
        $model = $this->validModel();
        $model->empleosProyectados = '4';
        $model->empleosJovenesProyectados = '5';

        self::assertFalse($model->validate());
        self::assertTrue($model->hasErrors('empleosJovenesProyectados'));
    }

    public function testConstructionDocumentsAreConditional(): void
    {
        $withoutConstruction = $this->validModel();
        self::assertTrue($withoutConstruction->validate());

        $withConstruction = $this->validModel();
        $withConstruction->incluyeObra = true;
        self::assertFalse($withConstruction->validate());
        self::assertTrue($withConstruction->hasErrors('cronogramaObra'));
        self::assertTrue($withConstruction->hasErrors('planosObra'));
    }

    public function testPostedFileNamesCanBeLoadedBeforeUploadedFiles(): void
    {
        $model = new SolicitudForm();

        self::assertTrue($model->load([
            'SolicitudForm' => [
                'peticionFormal' => 'peticion.pdf',
                'rucDocumento' => 'ruc.pdf',
            ],
        ]));
        self::assertSame('peticion.pdf', $model->peticionFormal);
    }

    private function validModel(): SolicitudForm
    {
        $model = new SolicitudForm([
            'razonSocial' => 'Inversiones Montecristi',
            'tipoPersona' => 'Jurídica',
            'ruc' => '1399999999001',
            'email' => 'inversionista@example.com',
            'telefono' => '0999999999',
            'direccionInversionista' => 'Av. Metropolitana',
            'domicilioTributario' => 'Montecristi, Manabí',
            'nombreProyecto' => 'Planta productiva',
            'descripcionProyecto' => 'Proyecto productivo',
            'ubicacion' => 'Montecristi, Manabí',
            'parroquia' => 'Montecristi',
            'tipoFuente' => 'Recursos propios',
            'entidadFinanciera' => 'Fondos propios',
            'fuentesFinanciamiento' => 'Capital propio',
            'detalleInversion' => 'Equipos e infraestructura',
            'montoInversion' => '100000',
            'cargoEmpleo' => 'Operador',
            'tipoContrato' => 'Indefinido',
            'empleosProyectados' => '10',
            'empleosJovenesProyectados' => '4',
            'declaracionAntilavado' => true,
            'documentacionFirmada' => true,
        ]);
        foreach (SolicitudForm::DOCUMENT_ATTRIBUTES as $attribute) {
            $model->{$attribute} = new UploadedFile([
                'name' => $attribute . '.pdf',
                'tempName' => dirname(__DIR__, 3) . '/_data/documento-prueba.pdf',
                'type' => 'application/pdf',
                'size' => 100,
                'error' => UPLOAD_ERR_OK,
            ]);
        }

        return $model;
    }
}
