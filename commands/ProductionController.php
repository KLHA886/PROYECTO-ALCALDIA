<?php

declare(strict_types=1);

namespace app\commands;

use app\components\Environment;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\FileHelper;

final class ProductionController extends Controller
{
    public function actionCheck(): int
    {
        $errors = [];
        $warnings = [];

        if (Environment::string('APP_ENV', 'dev') !== 'prod') {
            $warnings[] = 'APP_ENV todavía no está configurado como prod.';
        }
        if (Environment::bool('APP_DEBUG', true)) {
            $errors[] = 'APP_DEBUG debe ser false.';
        }
        if (strlen(Environment::string('APP_COOKIE_VALIDATION_KEY')) < 32) {
            $errors[] = 'APP_COOKIE_VALIDATION_KEY debe tener al menos 32 caracteres.';
        }
        if (Environment::string('DB_PASSWORD') === '') {
            $errors[] = 'DB_PASSWORD no puede estar vacío.';
        }
        if (Environment::string('MAILER_DSN') === '') {
            $errors[] = 'MAILER_DSN no está configurado; el correo seguiría en modo archivo.';
        }
        if (str_ends_with(Yii::$app->params['adminEmail'], '@example.com')) {
            $errors[] = 'ADMIN_EMAIL sigue usando el dominio de ejemplo.';
        }
        if (str_ends_with(Yii::$app->params['senderEmail'], '@example.com')) {
            $errors[] = 'SENDER_EMAIL sigue usando el dominio de ejemplo.';
        }

        try {
            Yii::$app->db->open();
            Yii::$app->db->createCommand('SELECT 1')->queryScalar();
            $pendingRotation = (int) (new \yii\db\Query())
                ->from('usuario')
                ->where(['must_change_password' => 1])
                ->count();
            if ($pendingRotation > 0) {
                $errors[] = "$pendingRotation cuenta(s) conservan credenciales iniciales.";
            }
        } catch (\Throwable) {
            $errors[] = 'No fue posible conectar con la base de datos.';
        }

        foreach ($warnings as $warning) {
            $this->stdout("[ADVERTENCIA] $warning\n");
        }
        foreach ($errors as $error) {
            $this->stderr("[ERROR] $error\n");
        }

        if ($errors !== []) {
            $this->stderr("Configuración no apta para producción.\n");
            return ExitCode::CONFIG;
        }

        $this->stdout("Configuración apta para producción.\n");
        return ExitCode::OK;
    }

    public function actionBackupCheck(): int
    {
        $defaultDumpPath = PHP_OS_FAMILY === 'Windows'
            ? 'C:\xampp\mysql\bin\mysqldump.exe'
            : '/usr/bin/mysqldump';
        $dumpPath = Environment::string('MYSQLDUMP_PATH', $defaultDumpPath);
        $backupDirectory = Yii::getAlias('@runtime/backups');
        FileHelper::createDirectory($backupDirectory, 0770);

        if (!is_file($dumpPath)) {
            $this->stderr("MYSQLDUMP_PATH no apunta a un archivo válido.\n");
            return ExitCode::CONFIG;
        }
        if (!is_writable($backupDirectory)) {
            $this->stderr("El directorio de respaldos no tiene permisos de escritura.\n");
            return ExitCode::NOPERM;
        }

        $this->stdout("Herramienta declarada: $dumpPath\n");
        $this->stdout("Directorio de respaldos disponible: $backupDirectory\n");
        $this->stdout("No se generó ningún respaldo durante esta comprobación.\n");
        return ExitCode::OK;
    }
}
