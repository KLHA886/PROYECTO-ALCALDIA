<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\User;

final class UserTest extends \Codeception\Test\Unit
{
    public function testFindUserById()
    {
        /** @var User $user */
        $user = User::findIdentity(100);

        verify($user)->notEmpty();
        verify($user->username)->equals('admin');
        verify(User::findIdentity(999))->empty();
    }

    public function testFindUserByAccessToken()
    {
        /** @var User $user */
        $user = User::findIdentityByAccessToken('100-token');

        verify($user)->notEmpty();
        verify($user->username)->equals('admin');
        verify(User::findIdentityByAccessToken('non-existing'))->empty();
    }

    public function testFindUserByUsername()
    {
        /** @var User $user */
        $user = User::findByUsername('admin');

        verify($user)->notEmpty();
        verify(User::findByUsername('not-admin'))->empty();
    }

    /**
     * @depends testFindUserByUsername
     */
    public function testValidateUser()
    {
        /** @var User $user */
        $user = User::findByUsername('admin');

        verify($user->validateAuthKey('test100key'))->notEmpty();
        verify($user->validateAuthKey('test102key'))->empty();
    }

    public function testRolePermissions(): void
    {
        $admin = User::findByUsername('admin');
        $reviewer = User::findByUsername('revisor');
        $investor = User::findByUsername('demo');

        self::assertNotNull($admin);
        self::assertNotNull($reviewer);
        self::assertNotNull($investor);
        self::assertTrue($admin->can(User::PERMISSION_UPDATE_PROJECT_STATUS));
        self::assertTrue($reviewer->can(User::PERMISSION_REVIEW_PROJECTS));
        self::assertTrue($reviewer->can(User::PERMISSION_DOWNLOAD_DOCUMENTS));
        self::assertFalse($investor->can(User::PERMISSION_REVIEW_PROJECTS));
    }
}
