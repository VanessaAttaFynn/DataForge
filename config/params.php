<?php

$mail = require __DIR__ . '/mail-local.php';

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => $mail['username'],
    'senderName' => 'DataForge',
    'user.passwordResetTokenExpire' => 18000, // reset links work for 5 hours
];
