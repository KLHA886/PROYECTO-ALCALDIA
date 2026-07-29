<?php

declare(strict_types=1);

use yii\db\Migration;

// phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
final class m260729_230000_add_project_audit_and_notifications extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('historial_proyecto', [
            'id' => $this->primaryKey(),
            'proyecto_id' => $this->integer()->notNull(),
            'actor' => $this->string(100)->notNull(),
            'accion' => $this->string(100)->notNull(),
            'estado_anterior' => $this->string(30),
            'estado_nuevo' => $this->string(30),
            'detalle' => $this->text(),
            'fecha' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx_historial_proyecto_fecha', 'historial_proyecto', ['proyecto_id', 'fecha']);
        $this->addForeignKey(
            'fk_historial_proyecto',
            'historial_proyecto',
            'proyecto_id',
            'proyecto',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->createTable('notificacion', [
            'id' => $this->primaryKey(),
            'proyecto_id' => $this->integer()->notNull(),
            'destinatario' => $this->string(150)->notNull(),
            'asunto' => $this->string(255)->notNull(),
            'mensaje' => $this->text()->notNull(),
            'estado' => "ENUM('Pendiente','Enviada','Fallida') NOT NULL DEFAULT 'Pendiente'",
            'intentos' => $this->integer()->notNull()->defaultValue(0),
            'ultimo_error' => $this->text(),
            'fecha_creacion' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'fecha_envio' => $this->dateTime(),
        ]);
        $this->createIndex('idx_notificacion_estado', 'notificacion', ['estado', 'fecha_creacion']);
        $this->addForeignKey(
            'fk_notificacion_proyecto',
            'notificacion',
            'proyecto_id',
            'proyecto',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('notificacion');
        $this->dropTable('historial_proyecto');
    }
}
