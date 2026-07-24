<?php
declare(strict_types=1);

define('MAIL_FROM_ADDRESS', 'tooltrack@example.com');
define('MAIL_FROM_NAME', 'ToolTrack Pro');

/*
PHP mail() must be configured on the server.

For production, replace delivery_mail() in:
notifications/_common.php

with PHPMailer, Symfony Mailer, SendGrid, Mailgun, Amazon SES,
or another authenticated SMTP/API provider.
*/
