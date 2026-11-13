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
