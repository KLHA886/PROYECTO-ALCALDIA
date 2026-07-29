<?php

declare(strict_types=1);

namespace app\modules\inversion\services;

use Throwable;
use Yii;
use yii\db\Connection;
use yii\helpers\Html;
use yii\mail\MailerInterface;

final class ProyectoNotifier
{
    public function __construct(
        private readonly ?Connection $connection = null,
        private readonly ?MailerInterface $mailer = null,
    ) {
    }

    public function notify(int $projectId, string $recipient, string $subject, string $message): void
    {
        if ($recipient === '') {
            return;
        }

        $db = $this->connection ?? Yii::$app->db;
        $notificationId = 0;

        try {
            $db->createCommand()->insert('notificacion', [
                'proyecto_id' => $projectId,
                'destinatario' => $recipient,
                'asunto' => $subject,
                'mensaje' => $message,
            ])->execute();
            $notificationId = (int) $db->getLastInsertID();
            $sent = ($this->mailer ?? Yii::$app->mailer)
                ->compose()
                ->setTo($recipient)
                ->setFrom([
                    Yii::$app->params['senderEmail'] => Yii::$app->params['senderName'],
                ])
                ->setSubject($subject)
                ->setHtmlBody($this->htmlMessage($subject, $message))
                ->setTextBody($message)
                ->send();

            $db->createCommand()->update('notificacion', [
                'estado' => $sent ? 'Enviada' : 'Fallida',
                'intentos' => 1,
                'fecha_envio' => $sent ? date('Y-m-d H:i:s') : null,
                'ultimo_error' => $sent ? null : 'El transporte de correo rechazó el mensaje.',
            ], ['id' => $notificationId])->execute();
        } catch (Throwable $exception) {
            if ($notificationId > 0) {
                try {
                    $db->createCommand()->update('notificacion', [
                        'estado' => 'Fallida',
                        'intentos' => 1,
                        'ultimo_error' => mb_substr($exception->getMessage(), 0, 2000),
                    ], ['id' => $notificationId])->execute();
                } catch (Throwable) {
                    // The workflow must remain successful even if notification logging is unavailable.
                }
            }
            Yii::warning('No se pudo enviar la notificación #' . $notificationId, __METHOD__);
        }
    }

    private function htmlMessage(string $subject, string $message): string
    {
        return '<div style="font-family:Arial,sans-serif;max-width:640px;margin:auto">'
            . '<h2 style="color:#3a9cbd">' . Html::encode($subject) . '</h2>'
            . '<p style="line-height:1.6">' . nl2br(Html::encode($message)) . '</p>'
            . '<hr><small>Ventanilla Única de Inversiones · GADM Montecristi</small>'
            . '</div>';
    }
}
