<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use GuzzleHttp\Client;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\PromotionLineItemFactory;
use Shopware\Core\Checkout\Cart\SalesChannel\CartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Tax\TaxDetector;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\JWT\JWTDecoder;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Theme\ConfigLoader\AbstractAvailableThemeProvider;
use Shopware\Storefront\Theme\ResolvedConfigLoader;
use Swag\PayPal\AgenticCommerce\HoneyWebhookController;
use Swag\PayPal\AgenticCommerce\HoneyWebhookService;
use Swag\PayPal\AgenticCommerce\Routing\AgentContextResolverListener;
use Swag\PayPal\AgenticCommerce\Routing\AgentRequestContextResolver;
use Swag\PayPal\AgenticCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgenticCommerce\SalesChannel\CheckoutRoute;
use Swag\PayPal\AgenticCommerce\SalesChannel\CreateCartRoute;
use Swag\PayPal\AgenticCommerce\SalesChannel\GetCartRoute;
use Swag\PayPal\AgenticCommerce\SalesChannel\Response\AgentResponseExceptionSubscriber;
use Swag\PayPal\AgenticCommerce\SalesChannel\UpdateCartRoute;
use Swag\PayPal\AgenticCommerce\Security\AbstractPayPalJwksProvider;
use Swag\PayPal\AgenticCommerce\Security\PayPalJwksProvider;
use Swag\PayPal\AgenticCommerce\Subscriber\OrderFlagSubscriber;
use Swag\PayPal\AgenticCommerce\Subscriber\ProductFilterSubscriber;
use Swag\PayPal\AgenticCommerce\Subscriber\WebhookSubscriber;
use Swag\PayPal\AgenticCommerce\Tax\AgenticTaxDetector;
use Swag\PayPal\AgenticCommerce\Util\AgentDebugIDProcessor;
use Swag\PayPal\AgenticCommerce\Util\FaviconLoader;
use Swag\PayPal\AgenticCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\AgenticCommerce\Util\ShopwareCartTransformer;
use Swag\PayPal\AgenticCommerce\Validation\ValidationIssues;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('monolog.processor.paypal.agent-debug-id', AgentDebugIDProcessor::class)
        ->call('setRequestStack', [service('request_stack')])
        ->call('setRouteScopeRegistry', [service(RouteScopeRegistry::class)])
        ->tag('monolog.processor', ['channel' => 'paypal']);

    $services->set(AgentRouteScope::class)
        ->tag('shopware.route_scope');

    $services->set(AbstractPayPalJwksProvider::class, PayPalJwksProvider::class)
        ->args([
            service(HttpClientInterface::class),
            service('cache.app'),
        ]);

    $services->set(AgentRequestContextResolver::class)
        ->args([
            service(DataValidator::class),
            service('product_export.repository'),
            service(JWTDecoder::class),
            service(RouteScopeRegistry::class),
            service(SalesChannelContextService::class),
            service(AbstractPayPalJwksProvider::class),
            service(CredentialsUtil::class),
        ]);

    $services->set(AgentContextResolverListener::class)
        ->args([
            service(AgentRequestContextResolver::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(AgentResponseExceptionSubscriber::class)
        ->args([
            service('monolog.logger.paypal'),
            service(RouteScopeRegistry::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(CreateCartRoute::class)
        ->public()
        ->args([
            service(SalesChannelContextService::class),
            service(CartService::class),
            service(PayPalCartTransformer::class),
            service(ShopwareCartTransformer::class),
            service(RegisterRoute::class),
            service(PayPalOrderBuilder::class),
            service(OrderResource::class),
        ]);

    $services->set(GetCartRoute::class)
        ->public()
        ->args([
            service(CartService::class),
            service(PayPalCartTransformer::class),
        ]);

    $services->set(UpdateCartRoute::class)
        ->public()
        ->args([
            service(SalesChannelContextService::class),
            service(ShopwareCartTransformer::class),
            service(CreateCartRoute::class),
            service('customer.repository'),
            service('customer_address.repository'),
            service(CartService::class),
            service(ContextSwitchRoute::class),
        ]);

    $services->set(CheckoutRoute::class)
        ->public()
        ->args([
            service(CartOrderRoute::class),
            service(CartService::class),
            service(OrderResource::class),
            service(PayPalPaymentHandler::class),
            service(PayPalCartTransformer::class),
        ]);

    $services->set(ValidationIssues::class)
        ->args([
            service(Translator::class),
        ]);

    $services->set(PayPalCartTransformer::class)
        ->args([
            service('product.repository'),
            service('country.repository'),
            service(ShippingMethodRoute::class),
            service(ValidationIssues::class),
        ]);

    $services->set(FaviconLoader::class)
        ->args([
            service(AbstractAvailableThemeProvider::class),
            service(ResolvedConfigLoader::class),
            service(SalesChannelContextService::class),
        ]);

    $services->set(ShopwareCartTransformer::class)
        ->args([
            service('sales_channel.country.repository'),
            service('salutation.repository'),
            service('customer_group.repository'),
            service(ProductLineItemFactory::class),
            service(PromotionLineItemFactory::class),
        ]);

    $services->set(ProductFilterSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(OrderFlagSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(WebhookSubscriber::class)
        ->args([
            service('sales_channel.repository'),
            service(HoneyWebhookService::class),
            service('notification.repository'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(AgenticTaxDetector::class)
        ->decorate(TaxDetector::class, null, -1000)
        ->args([
            service(AgenticTaxDetector::class . '.inner'),
            service('request_stack'),
        ]);

    $services->set('swag.paypal.honey_webhook', Client::class)
        ->lazy()
        ->args([
            [
                'base_uri' => 'https://d.joinhoney.com/',
                'timeout' => 20,
                'connect_timeout' => 20,
                'headers' => [
                    'Content-Type' => 'text/plain',
                ],
            ],
        ]);

    $services->set(HoneyWebhookService::class)
        ->args([
            service('swag.paypal.honey_webhook'),
            service('sales_channel.repository'),
            service(CredentialsUtil::class),
            service('router'),
            service(SystemConfigService::class),
            service('monolog.logger.paypal'),
            service(FaviconLoader::class),
        ]);

    $services->set(HoneyWebhookController::class)
        ->public()
        ->args([
            service(HoneyWebhookService::class),
        ])
        ->call('setContainer', [service('service_container')]);
};
