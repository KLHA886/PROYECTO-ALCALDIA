<?php

declare(strict_types=1);

namespace app\modules\inversion\services;

use app\models\User;
use Yii;
use Throwable;
use yii\db\Connection;

final class SecurityAudit
{
    public function __construct(private readonly ?Connection $connection = null)
    {
    }

    public function record(
        string $action,
        string $resource,
        ?int $resourceId,
        bool $success,
        ?string $detail = null,
        ?string $username = null,
    ): void {
        $request = Yii::$app->request;
        $ip = $request->userIP ?? '';
        $identity = Yii::$app->user->identity;
        $authenticatedUsername = $identity instanceof User ? $identity->username : null;
        try {
            ($this->connection ?? Yii::$app->db)->createCommand()->insert('auditoria_acceso', [
                'usuario' => $username ?? $authenticatedUsername,
                'accion' => $action,
                'recurso' => $resource,
                'recurso_id' => $resourceId,
                'ip_hash' => $ip === ''
                    ? null
                    : hash_hmac('sha256', $ip, Yii::$app->request->cookieValidationKey),
                'exitoso' => (int) $success,
                'detalle' => $detail === null ? null : mb_substr($detail, 0, 500),
            ])->execute();
        } catch (Throwable) {
            Yii::warning('No se pudo escribir la auditoría de seguridad.', __METHOD__);
        }
    }
}
