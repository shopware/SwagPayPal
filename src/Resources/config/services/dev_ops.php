<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Swag\PayPal\DevOps\Command\CrawlWebhookEventNames;
use Swag\PayPal\DevOps\Command\GenerateOpenApi;
use Swag\PayPal\DevOps\Command\LocaleScraperCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(GenerateOpenApi::class)
        ->tag('console.command');

    $services->set(LocaleScraperCommand::class)
        ->tag('console.command');

    $services->set(CrawlWebhookEventNames::class)
        ->tag('console.command');
};
