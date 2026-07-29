<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

final class UserController extends Controller
{
    public function actionCreate(string $username, string $role): int
    {
        if (!in_array($role, ['administrador', 'revisor', 'inversionista'], true)) {
            $this->stderr("Rol inválido.\n");
            return ExitCode::USAGE;
        }
        if ((new Query())->from('usuario')->where(['username' => $username])->exists()) {
            $this->stderr("El usuario ya existe.\n");
            return ExitCode::DATAERR;
        }

        $password = $this->securePassword();
        if ($password === null) {
            return ExitCode::CONFIG;
        }

        Yii::$app->db->createCommand()->insert('usuario', [
            'username' => $username,
            'password_hash' => Yii::$app->security->generatePasswordHash($password),
            'auth_key' => Yii::$app->security->generateRandomString(48),
            'access_token' => Yii::$app->security->generateRandomString(64),
            'role' => $role,
            'activo' => 1,
            'must_change_password' => 0,
        ])->execute();
        $this->stdout("Usuario creado correctamente. NEW_USER_PASSWORD no fue almacenada en texto.\n");
        return ExitCode::OK;
    }

    public function actionRotate(string $username): int
    {
        $password = $this->securePassword();
        if ($password === null) {
            return ExitCode::CONFIG;
        }

        $updated = Yii::$app->db->createCommand()->update('usuario', [
            'password_hash' => Yii::$app->security->generatePasswordHash($password),
            'auth_key' => Yii::$app->security->generateRandomString(48),
            'access_token' => Yii::$app->security->generateRandomString(64),
            'must_change_password' => 0,
        ], ['username' => $username])->execute();
        if ($updated === 0) {
            $this->stderr("Usuario no encontrado.\n");
            return ExitCode::NOUSER;
        }

        $this->stdout("Credenciales rotadas correctamente.\n");
        return ExitCode::OK;
    }

    private function securePassword(): ?string
    {
        $password = getenv('NEW_USER_PASSWORD');
        if ($password === false || strlen($password) < 12) {
            $this->stderr("Defina NEW_USER_PASSWORD con un mínimo de 12 caracteres.\n");
            return null;
        }

        return $password;
    }
}
