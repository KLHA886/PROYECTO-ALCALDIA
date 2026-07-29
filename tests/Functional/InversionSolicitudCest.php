<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\tests\Support\FunctionalTester;

final class InversionSolicitudCest
{
    public function openForm(FunctionalTester $I): void
    {
        $I->amOnRoute('inversion/solicitud/crear');
        $I->seeResponseCodeIsSuccessful();
        $I->see('Nueva solicitud de inversión', 'h1');
        $I->seeElement('#solicitud-inversion-form');
        $I->see('Proyecto de inversión');
        $I->see('Requisitos documentales');
    }

    public function rejectEmptySubmission(FunctionalTester $I): void
    {
        $I->amOnRoute('inversion/solicitud/crear');
        $I->submitForm('#solicitud-inversion-form', []);
        $I->see('Revise la información ingresada');
        $I->see('RUC cannot be blank');
        $I->see('Petición formal dirigida a la Alcaldía cannot be blank');
    }
}
