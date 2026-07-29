<?php

declare(strict_types=1);

use yii\db\Migration;

// phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
final class m260729_220000_add_document_review_workflow extends Migration
{
    public function safeUp(): void
    {
        $this->alterColumn(
            'proyecto',
            'estado',
            "ENUM('Borrador','Presentado','En revisión','Subsanación','Aprobado','Rechazado') DEFAULT 'Borrador'",
        );

        $this->createTable('observacion_documento', [
            'id' => $this->primaryKey(),
            'documento_id' => $this->integer()->notNull(),
            'autor' => $this->string(100)->notNull(),
            'observacion' => $this->text()->notNull(),
            'estado' => "ENUM('Pendiente','Subsanada','Aceptada') NOT NULL DEFAULT 'Pendiente'",
            'fecha_creacion' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'fecha_subsanacion' => $this->dateTime(),
        ]);
        $this->addForeignKey(
            'fk_observacion_documento',
            'observacion_documento',
            'documento_id',
            'documento',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->createTable('documento_version', [
            'id' => $this->primaryKey(),
            'documento_id' => $this->integer()->notNull(),
            'version' => $this->integer()->notNull(),
            'nombre_archivo' => $this->string(255)->notNull(),
            'ruta_archivo' => $this->string(255)->notNull(),
            'subido_por' => $this->string(100)->notNull(),
            'fecha_subida' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex(
            'uq_documento_version',
            'documento_version',
            ['documento_id', 'version'],
            true,
        );
        $this->addForeignKey(
            'fk_version_documento',
            'documento_version',
            'documento_id',
            'documento',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('documento_version');
        $this->dropTable('observacion_documento');
        $this->alterColumn(
            'proyecto',
            'estado',
            "ENUM('Borrador','Presentado','En revisión','Aprobado','Rechazado') DEFAULT 'Borrador'",
        );
    }
}
