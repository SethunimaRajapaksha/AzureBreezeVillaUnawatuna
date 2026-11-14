<?php
// config/config.php - site-wide settings

// BASE_URL is detected automatically from the folder name inside htdocs,
// so the project works from any folder (azure-breeze-villa, AzureBreezeVillaUnawatuna, ...).
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$docRoot     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$baseUrl     = '';
if ($docRoot !== '' && stripos($projectRoot, $docRoot) === 0) {
    $baseUrl = rtrim(substr($projectRoot, strlen($docRoot)), '/');
}
define('BASE_URL', $baseUrl);

define('SITE_NAME', 'Azure Breeze Villa');
define('CURRENCY', 'LKR');

// ---- Villa contact details: change them here and they update on every page ----
define('CONTACT_ADDRESS',  'No. 42, Matara Road, Unawatuna, Sri Lanka');
define('CONTACT_PHONE',    '+94 91 224 5678');
define('CONTACT_EMAIL',    'azurebreezevilla@unawatuna.lk');
define('CONTACT_CHECKIN',  '2:00 PM');
define('CONTACT_CHECKOUT', '12:00 AM');
define('CONTACT_WHATSAPP', '94771234567'); 
