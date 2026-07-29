<?php

declare(strict_types=1);

namespace app\models;

use yii\base\BaseObject;
use yii\db\Query;
use yii\web\IdentityInterface;

class User extends BaseObject implements IdentityInterface
{
    public const ROLE_ADMIN = 'administrador';
    public const ROLE_REVIEWER = 'revisor';
    public const ROLE_INVESTOR = 'inversionista';

    public const PERMISSION_REVIEW_PROJECTS = 'reviewProjects';
    public const PERMISSION_UPDATE_PROJECT_STATUS = 'updateProjectStatus';
    public const PERMISSION_DOWNLOAD_DOCUMENTS = 'downloadDocuments';

    public int|string $id = '';
    public string $username = '';
    public string $role = self::ROLE_INVESTOR;
    public string $passwordHash = '';
    public string $authKey = '';
    public string $accessToken = '';
    public bool $active = true;
    public bool $mustChangePassword = false;
    private static array $_users = [
        '100' => [
            'id' => '100',
            'username' => 'admin',
            'role' => self::ROLE_ADMIN,
            // password: admin
            'passwordHash' => '$2y$13$gYAywKSkhfZDq9FLNdm7buKnvlRxDexf5xipSMAxQPDUxpaptmZJu',
            'authKey' => 'test100key',
            'accessToken' => '100-token',
        ],
        '101' => [
            'id' => '101',
            'username' => 'demo',
            'role' => self::ROLE_INVESTOR,
            // password: demo
            'passwordHash' => '$2y$13$alRLq1PGVMlGYwS/Y3iy3ewQns1Z8ol8Iq6Zb5k7ZwEhblA1aL29y',
            'authKey' => 'test101key',
            'accessToken' => '101-token',
        ],
        '102' => [
            'id' => '102',
            'username' => 'revisor',
            'role' => self::ROLE_REVIEWER,
            // password: revisor
            'passwordHash' => '$2y$13$Ex/AzSbVPGHLVkl2fUtveuw..o0MKvY387F8.ZBxEllGtGP3drIPK',
            'authKey' => 'test102key',
            'accessToken' => '102-token',
        ],
    ];
    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id): static|null
    {
        if (self::databaseAvailable()) {
            return self::fromDatabase(['id' => $id, 'activo' => 1]);
        }

        return isset(self::$_users[$id]) ? new static(self::$_users[$id]) : null;
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null): static|null
    {
        if (self::databaseAvailable()) {
            return self::fromDatabase(['access_token' => $token, 'activo' => 1]);
        }

        foreach (self::$_users as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername(string $username): static|null
    {
        if (self::databaseAvailable()) {
            return self::fromDatabase(['username' => $username, 'activo' => 1]);
        }

        foreach (self::$_users as $user) {
            if (strcasecmp($user['username'], $username) === 0) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey(): string|null
    {
        return $this->authKey;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey): bool
    {
        return $this->authKey === $authKey;
    }

    public function can(string $permission): bool
    {
        $permissions = [
            self::ROLE_ADMIN => [
                self::PERMISSION_REVIEW_PROJECTS,
                self::PERMISSION_UPDATE_PROJECT_STATUS,
                self::PERMISSION_DOWNLOAD_DOCUMENTS,
            ],
            self::ROLE_REVIEWER => [
                self::PERMISSION_REVIEW_PROJECTS,
                self::PERMISSION_UPDATE_PROJECT_STATUS,
                self::PERMISSION_DOWNLOAD_DOCUMENTS,
            ],
            self::ROLE_INVESTOR => [],
        ];

        return in_array($permission, $permissions[$this->role] ?? [], true);
    }

    public function recordLogin(): void
    {
        if (!self::databaseAvailable()) {
            return;
        }

        \Yii::$app->db->createCommand()
            ->update('usuario', ['ultimo_acceso' => date('Y-m-d H:i:s')], ['id' => $this->id])
            ->execute();
    }

    /**
     * @param array<string, mixed> $condition
     */
    private static function fromDatabase(array $condition): static|null
    {
        $row = (new Query())->from('usuario')->where($condition)->one();
        if ($row === false) {
            return null;
        }

        return new static([
            'id' => (int) $row['id'],
            'username' => (string) $row['username'],
            'passwordHash' => (string) $row['password_hash'],
            'authKey' => (string) $row['auth_key'],
            'accessToken' => (string) $row['access_token'],
            'role' => (string) $row['role'],
            'active' => (bool) $row['activo'],
            'mustChangePassword' => (bool) $row['must_change_password'],
        ]);
    }

    private static function databaseAvailable(): bool
    {
        try {
            return \Yii::$app->db->schema->getTableSchema('usuario', true) !== null;
        } catch (\Throwable) {
            return false;
        }
    }
}
