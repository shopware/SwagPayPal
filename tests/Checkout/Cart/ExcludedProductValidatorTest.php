<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Cart;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\DataAbstractionLayer\ProductStreamUpdater;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\PartialEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\FullCheckoutTrait;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Util\PaymentMethodUtil;

/**
 * @internal
 */
#[Package('checkout')]
class ExcludedProductValidatorTest extends TestCase
{
    use FullCheckoutTrait;
    use IntegrationTestBehaviour;
    use PaymentMethodTrait;

    private ExcludedProductValidator $validator;

    private SystemConfigService $systemConfig;

    private IdsCollection $idsCollection;

    protected function setUp(): void
    {
        $this->validator = $this->getContainer()->get(ExcludedProductValidator::class);
        $this->systemConfig = $this->getContainer()->get(SystemConfigService::class);
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $paymentMethodUtil->reset();

        $this->idsCollection = new IdsCollection();
        $this->idsCollection->set('parent', $this->createProduct());
        $this->idsCollection->set('variant', $this->createProduct([
            'parentId' => $this->idsCollection->get('parent'),
        ]));

        $productStreamRepository = $this->getContainer()->get('product_stream.repository');
        $productStreamRepository->create([[
            'id' => $this->idsCollection->get('streamVariant'),
            'name' => 'only match variant product',
            'filters' => [[
                'type' => 'equals',
                'field' => 'product.id',
                'value' => $this->idsCollection->get('variant'),
                'position' => 1,
            ]],
        ], [
            'id' => $this->idsCollection->get('streamParent'),
            'name' => 'only match parent product',
            'filters' => [[
                'type' => 'equals',
                'field' => 'product.id',
                'value' => $this->idsCollection->get('parent'),
                'position' => 1,
            ]],
        ]], Context::createDefaultContext());

        $this->getContainer()->get(ProductStreamUpdater::class)->updateProducts(
            [
                $this->idsCollection->get('parent'),
                $this->idsCollection->get('variant'),
            ],
            Context::createDefaultContext()
        );
    }

    #[DataProvider('dataProviderConstellations')]
    public function testCartContainsExcludedProduct(?string $settingKey, ?string $settingIdName, ?string $expectedIdName): void
    {
        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)]);
        }

        $context = $this->registerUser();
        $cart = $this->addToCart($this->idsCollection->get('variant'), $context);

        static::assertSame((bool) $expectedIdName, $this->validator->cartContainsExcludedProduct($cart, $context));
    }

    #[DataProvider('dataProviderConstellations')]
    public function testCartContainsExcludedProductOnlyInSalesChannel(?string $settingKey, ?string $settingIdName, ?string $expectedIdName): void
    {
        $context = $this->registerUser();

        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)], $context->getSalesChannelId());
        }

        $cart = $this->addToCart($this->idsCollection->get('variant'), $context);

        static::assertSame((bool) $expectedIdName, $this->validator->cartContainsExcludedProduct($cart, $context));
    }

    #[DataProvider('dataProviderConstellations')]
    public function testFindExcludedProducts(?string $settingKey, ?string $settingIdName, ?string $expectedIdName): void
    {
        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)]);
        }

        static::assertSame(
            $expectedIdName ? [$this->idsCollection->get($expectedIdName)] : [],
            $this->validator->findExcludedProducts([$this->idsCollection->get($expectedIdName ?? 'variant')], $this->registerUser())
        );
    }

    #[DataProvider('dataProviderConstellations')]
    public function testIsExcludedProduct(?string $settingKey, ?string $settingIdName, ?string $expectedIdName): void
    {
        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)]);
        }

        $product = $this->getContainer()->get('product.repository')->search(
            new Criteria([$this->idsCollection->get('variant')]),
            Context::createDefaultContext()
        )->getEntities()->first();
        static::assertInstanceOf(ProductEntity::class, $product);

        static::assertSame(
            (bool) $expectedIdName,
            $this->validator->isProductExcluded($product, $this->registerUser())
        );
    }

    /**
     * this test is related to the ExpressCheckoutSubscriber
     */
    #[DataProvider('dataProviderConstellations')]
    public function testExcludedProductTaggedInSearchResults(?string $settingKey, ?string $settingIdName, ?string $expectedIdName): void
    {
        $this->enableExpressCheckoutForListings();

        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)]);
        }

        $productRepository = $this->getContainer()->get('sales_channel.product.repository');
        $products = $productRepository->search(
            new Criteria([$this->idsCollection->get('variant')]),
            $this->registerUser()
        )->getEntities();
        static::assertInstanceOf(SalesChannelProductCollection::class, $products);
        static::assertNotEmpty($products);

        // the searched variant is excluded, whenever it is excluded itself or through its parent
        foreach ($products as $product) {
            static::assertSame(
                $expectedIdName !== null,
                $product->hasExtension(ExcludedProductValidator::PRODUCT_EXCLUDED_FOR_PAYPAL)
            );
        }
    }

    /**
     * this test is related to the ExpressCheckoutSubscriber
     */
    #[DataProvider('dataProviderConstellations')]
    public function testExcludedProductTaggedInSearchResultsWithListingDisabled(?string $settingKey, ?string $settingIdName, ?string $_expectedIdName): void
    {
        $this->enableExpressCheckoutForListings();

        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)]);
        }
        $this->systemConfig->set(Settings::ECS_LISTING_ENABLED, false);

        $productRepository = $this->getContainer()->get('sales_channel.product.repository');
        $products = $productRepository->search(
            new Criteria([$this->idsCollection->get('variant')]),
            $this->registerUser()
        )->getEntities();
        static::assertNotEmpty($products);

        foreach ($products as $product) {
            static::assertFalse($product->hasExtension(ExcludedProductValidator::PRODUCT_EXCLUDED_FOR_PAYPAL));
        }
    }

    /**
     * this test is related to the ExpressCheckoutSubscriber
     */
    public function testExcludedProductTaggedInPartialSearchResults(): void
    {
        $this->enableExpressCheckoutForListings();
        $this->systemConfig->set(Settings::EXCLUDED_PRODUCT_IDS, [$this->idsCollection->get('parent')]);

        $variant = $this->searchPartialVariant(['parentId'], $this->registerUser());

        static::assertTrue($variant->hasExtension(ExcludedProductValidator::PRODUCT_EXCLUDED_FOR_PAYPAL));
    }

    /**
     * this test is related to the ExpressCheckoutSubscriber
     */
    public function testExcludedProductTaggedInPartialSearchResultsWithoutParentId(): void
    {
        $this->enableExpressCheckoutForListings();
        $context = $this->registerUser();

        // the field selection of Shopware\Core\Content\Product\SalesChannel\PurchaseLimit\ProductPurchaseLimitRoute
        $fields = ['minPurchase', 'maxPurchase', 'purchaseSteps', 'isCloseout', 'stock'];

        // without the parentId, an exclusion through the parent product cannot be detected anymore
        $this->systemConfig->set(Settings::EXCLUDED_PRODUCT_IDS, [$this->idsCollection->get('parent')]);
        $variant = $this->searchPartialVariant($fields, $context);

        static::assertFalse($variant->has('parentId'));
        static::assertFalse($variant->hasExtension(ExcludedProductValidator::PRODUCT_EXCLUDED_FOR_PAYPAL));

        // the product's own id is always loaded, as it is the primary key
        $this->systemConfig->set(Settings::EXCLUDED_PRODUCT_IDS, [$this->idsCollection->get('variant')]);
        $variant = $this->searchPartialVariant($fields, $context);

        static::assertTrue($variant->hasExtension(ExcludedProductValidator::PRODUCT_EXCLUDED_FOR_PAYPAL));
    }

    public static function dataProviderConstellations(): array
    {
        return [
            'nothingExcluded' => [
                null,
                null,
                null,
            ],
            'excludedVariant' => [
                Settings::EXCLUDED_PRODUCT_IDS,
                'variant',
                'variant',
            ],
            'excludedParent' => [
                Settings::EXCLUDED_PRODUCT_IDS,
                'parent',
                'parent',
            ],
            'excludedStreamVariant' => [
                Settings::EXCLUDED_PRODUCT_STREAM_IDS,
                'streamVariant',
                'variant',
            ],
            'excludedStreamParent' => [
                Settings::EXCLUDED_PRODUCT_STREAM_IDS,
                'streamParent',
                'parent',
            ],
        ];
    }

    /**
     * The ExpressCheckoutSubscriber only tags excluded products in search results, if the express
     * checkout is actually shown on listing pages.
     */
    private function enableExpressCheckoutForListings(): void
    {
        $this->systemConfig->set(Settings::CLIENT_ID, 'someClientId');
        $this->systemConfig->set(Settings::CLIENT_SECRET, 'someClientSecret');
        $this->systemConfig->set(Settings::ECS_LISTING_ENABLED, true);

        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $paymentMethodId = $paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());
        static::assertNotNull($paymentMethodId);

        $this->getContainer()->get('payment_method.repository')->update([[
            'id' => $paymentMethodId,
            'active' => true,
        ]], Context::createDefaultContext());
        $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);

        $paymentMethodUtil->reset();
    }

    /**
     * Criteria::addFields() makes the DAL hydrate PartialEntity instances, which only carry the
     * requested fields and have no typed getters.
     *
     * @param list<string> $fields
     */
    private function searchPartialVariant(array $fields, SalesChannelContext $context): PartialEntity
    {
        $criteria = new Criteria([$this->idsCollection->get('variant')]);
        $criteria->addFields($fields);

        $variant = $this->getContainer()->get('sales_channel.product.repository')
            ->search($criteria, $context)
            ->getEntities()
            ->get($this->idsCollection->get('variant'));

        static::assertInstanceOf(PartialEntity::class, $variant);

        return $variant;
    }
}
