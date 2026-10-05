<?php

$mail = require __DIR__ . '/mail-local.php';

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => $mail['username'],
    'senderName' => 'DataForge',
];
