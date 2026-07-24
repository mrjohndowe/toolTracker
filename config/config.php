<?php
declare(strict_types=1);

const APP_NAME = 'ToolTrack Pro';
const APP_VERSION = '1.0.0';
const BASE_URL = '/toolTracker';
const SESSION_TIMEOUT = 3600;
const DEVELEPER_NAME = "MrJohnDowe";
const DEVELOPED_YEAR = 2026;

date_default_timezone_set('America/Denver');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax',
    ]);
    session_start();
}
