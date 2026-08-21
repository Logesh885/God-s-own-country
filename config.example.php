<?php
// Copy this to config.php and update values
return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'misty_munnar',
        'user' => 'dbuser',
        'pass' => 'dbpass',
        'charset' => 'utf8mb4',
    ],

    // WhatsApp number in international format without + or spaces (example for +91 9751396418 => 919751396418)
    'whatsapp_number' => '919751396418',

    // From address for contact emails (if you configure mail)
    'site_email' => 'mistymunnartours@gmail.com',

    // Admin session settings
    'admin_session_name' => 'misty_admin',
];
