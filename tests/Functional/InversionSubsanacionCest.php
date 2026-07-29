<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\tests\Support\FunctionalTester;

final class InversionSubsanacionCest
{
    public function openCorrectionLookup(FunctionalTester $I): void
    {
        $I->amOnRoute('inversion/subsanacion/index');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Subsanar expediente', 'h1');
        $I->seeElement('#consulta-subsanacion-form');
    }

    public function rejectInvalidLookup(FunctionalTester $I): void
    {
        $I->amOnRoute('inversion/subsanacion/index');
        $I->submitForm('#consulta-subsanacion-form', [
            'ConsultaSubsanacionForm[codigo]' => 'incorrecto',
            'ConsultaSubsanacionForm[ruc]' => '123',
        ]);

        $I->see('Código del expediente is invalid');
        $I->see('RUC del inversionista is invalid');
    }
}
