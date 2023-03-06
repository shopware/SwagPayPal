<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRoute;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class FilteredPaymentMethodRouteTest extends TestCase
{
    use SalesChannelContextTrait;
    use PaymentMethodTrait;
    use ServicesTrait;

    private AbstractPaymentMethodRoute $paymentMethodRoute;

    private SystemConfigService $systemConfig;

    private string $pluginId;

    private string $productId;

    public function setUp(): void
    {
        $this->paymentMethodRoute = $this->getContainer()->get(PaymentMethodRoute::class);
        $this->systemConfig = $this->getContainer()->get(SystemConfigService::class);
        $this->pluginId = $this->getContainer()->get(PluginIdProvider::class)->getPluginIdByBaseClass(SwagPayPal::class, Context::createDefaultContext());

        foreach ($this->getDefaultConfigData() as $key => $value) {
            $this->systemConfig->set($key, $value);
        }

        $this->productId = Uuid::randomHex();
    }

    public function testRouteWithInvalidCredentials(): void
    {
        $this->systemConfig->delete(Settings::CLIENT_ID);
        $this->systemConfig->delete(Settings::CLIENT_SECRET);

        static::assertCount(0, $this->loadPaymentMethods(new Request(['onlyAvailable' => true])));
    }

    public function testRouteWithExistingCredentials(): void
    {
        static::assertNotCount(0, $this->loadPaymentMethods(new Request(['onlyAvailable' => true])));
    }

    public function testRouteWithZeroValueCart(): void
    {
        static::assertCount(0, $this->loadPaymentMethods(new Request(['onlyAvailable' => true]), 0.0));
    }

    public function testRouteWithExcludedProducts(): void
    {
        $this->systemConfig->set(Settings::EXCLUDED_PRODUCT_IDS, [$this->productId]);

        static::assertCount(0, $this->loadPaymentMethods(new Request(['onlyAvailable' => true])));
    }

    public function testRouteDoesNotInterfereWithUnavailable(): void
    {
        $this->systemConfig->delete(Settings::CLIENT_ID);
        $this->systemConfig->delete(Settings::CLIENT_SECRET);

        static::assertNotCount(0, $this->loadPaymentMethods(new Request()));
    }

    private function loadPaymentMethods(Request $request, float $price = 25.0): PaymentMethodCollection
    {
        $salesChannelContext = $this->getContainer()->get(SalesChannelContextService::class)->get(new SalesChannelContextServiceParameters(TestDefaults::SALES_CHANNEL, Uuid::randomHex()));

        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');
        $ids = $paymentMethodRepository->searchIds(
            (new Criteria())->addFilter(new EqualsFilter('pluginId', $this->pluginId)),
            Context::createDefaultContext()
        )->getIds();
        foreach ($ids as $id) {
            if (\is_string($id)) {
                $this->addPaymentMethodToDefaultsSalesChannel($id);
            }
        }

        $this->buildCart($price, $salesChannelContext);

        return $this->paymentMethodRoute
            ->load($request, $salesChannelContext, new Criteria())
            ->getPaymentMethods()
            ->filterByPluginId($this->pluginId);
    }

    private function buildCart(float $price, SalesChannelContext $salesChannelContext): void
    {
        /** @var EntityRepository $productRepository */
        $productRepository = $this->getContainer()->get(\sprintf('%s.repository', ProductDefinition::ENTITY_NAME));

        $productData = [
            'id' => $this->productId,
            'stock' => \random_int(1, 5),
            'taxId' => $this->getValidTaxId(),
            'price' => [
                'net' => [
                    'currencyId' => Defaults::CURRENCY,
                    'net' => $price,
                    'gross' => $price * 1.27,
                    'linked' => true,
                ],
            ],
            'productNumber' => 'test-234',
            'name' => 'example-product',
            'active' => true,
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $productRepository->create([$productData], $salesChannelContext->getContext());

        $cartService = $this->getContainer()->get(CartService::class);
        $cart = $cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        $lineItem = (new LineItem($this->productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $this->productId, 1))
            ->setRemovable(true)
            ->setStackable(true);

        $cartService->add($cart, $lineItem, $salesChannelContext);
    }
}
