<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;
use Shopware\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Shopware\Core\Framework\Adapter\Twig\NamespaceHierarchy\BundleHierarchyBuilder;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Contract\Gateway\TokenGatewayInterface;
use Shopware\PayPalSDK\Contract\RequestServiceInterface;
use Shopware\Storefront\Theme\Twig\ThemeInheritanceBuilderInterface;
use Swag\PayPal\Diagnostics\Administration\ExpressCallbackDiagnosticsController;
use Swag\PayPal\Diagnostics\Administration\TwigDiagnosticsController;
use Swag\PayPal\Diagnostics\Administration\WebhookDiagnosticsController;
use Swag\PayPal\Diagnostics\ExpressCallback\CallbackUrlBuilder;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackDiagnostics;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallbackStorage;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\StoreApiUrlStorage;
use Swag\PayPal\Diagnostics\ExpressCallback\TrustedHosts;
use Swag\PayPal\Diagnostics\Log\DataAbstractionLayer\LogLineDefinition;
use Swag\PayPal\Diagnostics\Log\Recording\DatabaseLogHandler;
use Swag\PayPal\Diagnostics\Log\Retention\LogCleanupTask;
use Swag\PayPal\Diagnostics\Log\Retention\LogCleanupTaskHandler;
use Swag\PayPal\Diagnostics\Reachability\HostResolver;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityProbe;
use Swag\PayPal\Diagnostics\Twig\Inheritance\NamespaceHierarchyResolver;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChainLoader;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideAnalyzer;
use Swag\PayPal\Diagnostics\Twig\Parser\TwigTemplateParser;
use Swag\PayPal\Diagnostics\Twig\TwigDiagnostics;
use Swag\PayPal\Diagnostics\Webhook\Api\WebhookEventGateway;
use Swag\PayPal\Diagnostics\Webhook\MissedEventsChecker;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\TestWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\WebhookDiagnostics;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Util\PaymentMethodUtil;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationChecker;
use Swag\PayPal\Webhook\Registration\WebhookUrlBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()->set('env(PAYPAL_DB_LOGGER)', '1');

    $services = $containerConfigurator->services();

    // Express Checkout callback
    $services->set(ExpressCallbackDiagnosticsController::class)
        ->public()
        ->args([
            service(ExpressCallbackDiagnostics::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(ExpressCallbackDiagnostics::class)
        ->args([
            service('sales_channel.repository'),
            service(SystemConfigService::class),
            service(PaymentMethodUtil::class),
            service(LastCallbackStorage::class),
            service(ReachabilityProbe::class),
            service(CallbackUrlBuilder::class),
            service(StoreApiUrlStorage::class),
            service(TrustedHosts::class),
            service(CredentialsUtil::class),
        ]);

    $services->set(LastCallbackStorage::class)
        ->args([
            service(AbstractKeyValueStorage::class),
            service(ClockInterface::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(CallbackUrlBuilder::class)
        ->args([
            service('router'),
            service(StoreApiUrlStorage::class),
            service(TrustedHosts::class),
        ]);

    $services->set(TrustedHosts::class)
        ->args([
            service('sales_channel_domain.repository'),
            env('APP_URL')->default('')->string(),
        ]);

    $services->set(StoreApiUrlStorage::class)
        ->args([
            service(AbstractKeyValueStorage::class),
            service('monolog.logger.paypal'),
        ]);

    // Webhook
    $services->set(WebhookDiagnosticsController::class)
        ->public()
        ->args([
            service(WebhookDiagnostics::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(WebhookDiagnostics::class)
        ->args([
            service('sales_channel.repository'),
            service(WebhookRegistrationChecker::class),
            service(WebhookUrlBuilder::class),
            service(SystemConfigService::class),
            service(LastWebhookStorage::class),
            service(TestWebhookStorage::class),
            service(ReachabilityProbe::class),
            service('monolog.logger.paypal'),
            service(ApiContextFactoryInterface::class),
            service(WebhookEventGateway::class),
            service(ClockInterface::class),
            service(MissedEventsChecker::class),
        ]);

    $services->set(MissedEventsChecker::class)
        ->args([
            service(WebhookEventGateway::class),
            service(ApiContextFactoryInterface::class),
            service(ClockInterface::class),
            service('monolog.logger.paypal'),
        ]);

    $services->set(WebhookEventGateway::class)
        ->args([
            service('paypal-sdk.client'),
            service(TokenGatewayInterface::class),
            service(RequestServiceInterface::class),
        ]);

    $services->set(LastWebhookStorage::class)
        ->args([
            service(AbstractKeyValueStorage::class),
            service(ClockInterface::class),
            service('monolog.logger.paypal'),
            service(TestWebhookStorage::class),
        ]);

    $services->set(TestWebhookStorage::class)
        ->args([
            service(AbstractKeyValueStorage::class),
            service('monolog.logger.paypal'),
        ]);

    // Reachability
    $services->set(HostResolver::class);

    $services->set(ReachabilityProbe::class)
        ->args([
            service(HttpClientInterface::class),
            service(HostResolver::class),
        ]);

    // Twig
    $services->set(TwigDiagnosticsController::class)
        ->public()
        ->args([
            service(TwigDiagnostics::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(TwigDiagnostics::class)
        ->args([
            service(NamespaceHierarchyResolver::class),
            service(TemplateChainLoader::class),
            service(TemplateOverrideAnalyzer::class),
            param('kernel.bundles_metadata'),
        ]);

    $services->set(NamespaceHierarchyResolver::class)
        ->args([
            service('sales_channel.repository'),
            service('theme.repository')->nullOnInvalid(),
            service(BundleHierarchyBuilder::class),
            service(ThemeInheritanceBuilderInterface::class)->nullOnInvalid(),
        ]);

    $services->set(TemplateChainLoader::class)
        ->args([
            service('twig.loader'),
            param('kernel.project_dir'),
        ]);

    $services->set(TwigTemplateParser::class)
        ->args([
            service('twig'),
        ]);

    $services->set(TemplateOverrideAnalyzer::class)
        ->args([
            service(TwigTemplateParser::class),
        ]);

    // Logs, added to the `paypal` channel in packages/monolog.yaml
    $services->set(DatabaseLogHandler::class)
        ->args([
            service(Connection::class),
            service(SystemConfigService::class),
            env('PAYPAL_DB_LOGGER')->bool(),
        ]);

    $services->set(LogLineDefinition::class)
        ->tag('shopware.entity.definition', ['entity' => LogLineDefinition::ENTITY_NAME]);

    $services->set(LogCleanupTask::class)
        ->tag('shopware.scheduled.task');

    $services->set(LogCleanupTaskHandler::class)
        ->args([
            service('scheduled_task.repository'),
            service('monolog.logger.paypal'),
            service(Connection::class),
            service(ClockInterface::class),
        ])
        ->arg('$maxAgeInDays', 30)
        ->arg('$maxLines', 100_000)
        ->arg('$batchSize', 10_000)
        ->tag('messenger.message_handler');
};
