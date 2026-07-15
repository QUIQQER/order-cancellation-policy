<?php

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

putenv('QUIQQER_OTHER_AUTOLOADERS=KEEP');

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/stubs/QUI/Captcha/Controls/CaptchaDisplay.php';
require_once __DIR__ . '/stubs/QUI/Captcha/Handler.php';
require_once __DIR__ . '/stubs/QUI/ERP/Order/SimpleCheckout/Checkout.php';

$cancellationPolicyPrefix = 'QUI\\ERP\\Order\\CancellationPolicy\\';
$cancellationPolicySource = dirname(__DIR__) . '/src/QUI/ERP/Order/CancellationPolicy/';

spl_autoload_register(
    static function (string $className) use ($cancellationPolicyPrefix, $cancellationPolicySource): void {
        if (!str_starts_with($className, $cancellationPolicyPrefix)) {
            return;
        }

        $relativeClass = substr($className, strlen($cancellationPolicyPrefix));
        $file = $cancellationPolicySource . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    },
    true,
    true
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

    $areasCount = (int)QUI::getQueryBuilder()
        ->select('COUNT(*)')
        ->from(QUI\Utils\Doctrine::quoteIdentifier($areasTable))
        ->executeQuery()
        ->fetchOne();

    if ($areasCount === 0) {
        throw new RuntimeException('The standard area test fixtures could not be imported.');
    }
}
