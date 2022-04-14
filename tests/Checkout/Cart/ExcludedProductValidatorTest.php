<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Cart;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\DataAbstractionLayer\ProductStreamUpdater;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\IdsCollection;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutSubscriber;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\FullCheckoutTrait;

class ExcludedProductValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;
    use FullCheckoutTrait;

    private ExcludedProductValidator $validator;

    private SystemConfigService $systemConfig;

    private IdsCollection $idsCollection;

    public function setUp(): void
    {
        $this->validator = $this->getContainer()->get(ExcludedProductValidator::class);
        $this->systemConfig = $this->getContainer()->get(SystemConfigService::class);

        $this->idsCollection = new IdsCollection();
        $this->idsCollection->set('parent', $this->createProduct());
        $this->idsCollection->set('variant', $this->createProduct([
            'parentId' => $this->idsCollection->get('parent'),
        ]));

        /** @var EntityRepositoryInterface $productStreamRepository */
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

    /**
     * @dataProvider dataProviderConstellations
     */
    public function testCartContainsExcludedProduct(?string $settingKey, ?string $settingIdName, ?string $expectedIdName): void
    {
        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)]);
        }

        $context = $this->registerUser();
        $cart = $this->addToCart($this->idsCollection->get('variant'), $context);

        static::assertSame((bool) $expectedIdName, $this->validator->cartContainsExcludedProduct($cart, $context));
    }

    /**
     * @dataProvider dataProviderConstellations
     */
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

    /**
     * @dataProvider dataProviderConstellations
     */
    public function testIsExcludedProduct(?string $settingKey, ?string $settingIdName, ?string $expectedIdName): void
    {
        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)]);
        }

        /** @var EntityRepositoryInterface $productRepository */
        $productRepository = $this->getContainer()->get('product.repository');
        $product = $productRepository->search(
            new Criteria([$this->idsCollection->get('variant')]),
            Context::createDefaultContext()
        )->first();
        static::assertInstanceOf(ProductEntity::class, $product);

        static::assertSame(
            (bool) $expectedIdName,
            $this->validator->isProductExcluded($product, $this->registerUser())
        );
    }

    /**
     * this test is related to the ExpressCheckoutSubscriber
     *
     * @dataProvider dataProviderConstellations
     */
    public function testExcludedProductTaggedInSearchResults(?string $settingKey, ?string $settingIdName, ?string $expectedIdName): void
    {
        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)]);
        }

        /** @var SalesChannelRepositoryInterface $productRepository */
        $productRepository = $this->getContainer()->get('sales_channel.product.repository');
        $products = $productRepository->search(
            new Criteria([$this->idsCollection->get('variant')]),
            $this->registerUser()
        )->getEntities();
        static::assertNotEmpty($products);

        foreach ($products as $product) {
            static::assertSame(
                $product->getId() === $expectedIdName,
                $product->hasExtension(ExcludedProductValidator::PRODUCT_EXCLUDED_FOR_PAYPAL)
            );
        }
    }

    /**
     * this test is related to the ExpressCheckoutSubscriber
     *
     * @dataProvider dataProviderConstellations
     */
    public function testExcludedProductTaggedInSearchResultsWithListingDisabled(?string $settingKey, ?string $settingIdName): void
    {
        if ($settingKey && $settingIdName) {
            $this->systemConfig->set($settingKey, [$this->idsCollection->get($settingIdName)]);
        }
        $this->systemConfig->set(Settings::ECS_LISTING_ENABLED, false);

        /** @var SalesChannelRepositoryInterface $productRepository */
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

    public function dataProviderConstellations(): array
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
}
