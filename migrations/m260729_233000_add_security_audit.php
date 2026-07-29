<?php

declare(strict_types=1);

use yii\db\Migration;

// phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
final class m260729_233000_add_security_audit extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('auditoria_acceso', [
            'id' => $this->primaryKey(),
            'usuario' => $this->string(100),
            'accion' => $this->string(100)->notNull(),
            'recurso' => $this->string(100)->notNull(),
            'recurso_id' => $this->integer(),
            'ip_hash' => $this->char(64),
            'exitoso' => $this->boolean()->notNull()->defaultValue(false),
            'detalle' => $this->string(500),
            'fecha' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex(
            'idx_auditoria_recurso_fecha',
            'auditoria_acceso',
            ['recurso', 'recurso_id', 'fecha'],
        );
        $this->createIndex(
            'idx_auditoria_usuario_fecha',
            'auditoria_acceso',
            ['usuario', 'fecha'],
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('auditoria_acceso');
    }
}
