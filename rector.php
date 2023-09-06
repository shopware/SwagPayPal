<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Swag\PayPal\DevOps\Rector\ClassCheckoutPackageRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->symfonyContainerXml(__DIR__ . '../../../var/cache/phpstan_dev/Shopware_Core_DevOps_StaticAnalyze_StaticAnalyzeKernelPhpstan_devDebugContainer.xml');

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->fileExtensions(['php']);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    $rectorConfig->skip([
        '**/vendor/*',
    ]);

    $rectorConfig->rule(ClassCheckoutPackageRector::class);
};
