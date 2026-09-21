<?php
// Copy to local.php on the server. Keep this directory OUTSIDE the public web root.
return [
    'smtp_host' => 'mail.alinbughius.ro',
    'smtp_port' => 465,
    'smtp_username' => 'contact@alinbughius.ro',
    'smtp_password' => getenv('SMTP_PASSWORD') ?: '',
    'from_email' => 'contact@alinbughius.ro',
    'owner_email' => 'bughius_alin@yahoo.com',
    'site_url' => 'https://alinbughius.ro',
];
