<?php

declare(strict_types=1);

use yii\db\Migration;

// phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
final class m260729_240000_add_database_users extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('usuario', [
            'id' => $this->primaryKey(),
            'username' => $this->string(80)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(64)->notNull(),
            'access_token' => $this->string(100)->notNull()->unique(),
            'role' => "ENUM('administrador','revisor','inversionista') NOT NULL",
            'activo' => $this->boolean()->notNull()->defaultValue(true),
            'must_change_password' => $this->boolean()->notNull()->defaultValue(true),
            'ultimo_acceso' => $this->dateTime(),
            'fecha_creacion' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->batchInsert(
            'usuario',
            [
                'id',
                'username',
                'password_hash',
                'auth_key',
                'access_token',
                'role',
                'activo',
                'must_change_password',
            ],
            [
                [
                    100,
                    'admin',
                    '$2y$13$gYAywKSkhfZDq9FLNdm7buKnvlRxDexf5xipSMAxQPDUxpaptmZJu',
                    'test100key',
                    '100-token',
                    'administrador',
                    1,
                    1,
                ],
                [
                    101,
                    'demo',
                    '$2y$13$alRLq1PGVMlGYwS/Y3iy3ewQns1Z8ol8Iq6Zb5k7ZwEhblA1aL29y',
                    'test101key',
                    '101-token',
                    'inversionista',
                    1,
                    1,
                ],
                [
                    102,
                    'revisor',
                    '$2y$13$Ex/AzSbVPGHLVkl2fUtveuw..o0MKvY387F8.ZBxEllGtGP3drIPK',
                    'test102key',
                    '102-token',
                    'revisor',
                    1,
                    1,
                ],
            ],
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('usuario');
    }
}
