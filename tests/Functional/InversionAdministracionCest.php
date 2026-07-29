<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\tests\Support\FunctionalTester;

final class InversionAdministracionCest
{
    public function guestIsRedirectedToLogin(FunctionalTester $I): void
    {
        $I->amOnRoute('inversion/administracion/index');

        $I->seeInCurrentUrl('site%2Flogin');
        $I->seeElement('#login-form');
    }
}
