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

$SchemaManager = QUI::getSchemaManager();
$areasTable = QUI::getDBTableName('areas');
$requestsTable = QUI::getDBTableName('requests');
$areasTableExists = $SchemaManager->tablesExist([$areasTable]);

if (!$areasTableExists) {
    QUI\Update::importDatabase(OPT_DIR . 'quiqqer/areas/database.xml');
}

$requiresDatabaseImport = !$SchemaManager->tablesExist([$requestsTable]);

if (!$requiresDatabaseImport) {
    $areasColumns = $SchemaManager->listTableColumns($areasTable);
    $requiresDatabaseImport = !isset($areasColumns['ocp']);
}

if ($requiresDatabaseImport) {
    QUI\Update::importDatabase(dirname(__DIR__) . '/database.xml');
}

$areasCount = (int)QUI::getQueryBuilder()
    ->select('COUNT(*)')
    ->from(QUI\Utils\Doctrine::quoteIdentifier($areasTable))
    ->executeQuery()
    ->fetchOne();

if ($areasCount === 0) {
    $PermissionUser = new ReflectionProperty(QUI\Permissions\Permission::class, 'User');
    $previousPermissionUser = $PermissionUser->getValue();
    QUI\Permissions\Permission::setUser(QUI::getUsers()->getSystemUser());

    try {
        QUI\ERP\Areas\Import::importPreconfigureAreas('DigitalGoodsFromEuropaToEuropa.xml');
    } finally {
        $PermissionUser->setValue(null, $previousPermissionUser);
    }
}
