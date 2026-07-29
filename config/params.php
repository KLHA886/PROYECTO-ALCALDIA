<?php

use app\components\Environment;

require_once dirname(__DIR__) . '/components/Environment.php';

return [
    'adminEmail' => Environment::string('ADMIN_EMAIL', 'admin@example.com'),
    'senderEmail' => Environment::string('SENDER_EMAIL', 'noreply@example.com'),
    'senderName' => Environment::string('SENDER_NAME', 'Ventanilla de Inversiones'),
    'documentRetentionDays' => Environment::int('DOCUMENT_RETENTION_DAYS', 1825),
];
