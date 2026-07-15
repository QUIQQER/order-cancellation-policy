<?php

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

putenv('QUIQQER_OTHER_AUTOLOADERS=KEEP');

require_once __DIR__ . '/../../../../bootstrap.php';

QUI\Autoloader::$ComposerLoader?->addPsr4(
    'QUI\\ERP\\Order\\CancellationPolicy\\',
    dirname(__DIR__) . '/src/QUI/ERP/Order/CancellationPolicy'
);
