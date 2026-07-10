<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\Exception\SalesChannelNotFoundException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Util\PaymentMethodUtil;

/**
 * @internal
 */
#[Package('checkout')]
trait CheckoutRouteTrait
{
    use PaymentMethodTrait;
    use ServicesTrait;

    protected function tearDown(): void
    {
        $context = Context::createDefaultContext();
        $paymentMethodId = $this->getContainer()->get(PaymentMethodUtil::class)->getPayPalPaymentMethodId($context);
        if ($paymentMethodId !== null) {
            $this->removePaymentMethodFromDefaultsSalesChannel($paymentMethodId, $context);
        }
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        $paymentMethod = $this->getAvailablePaymentMethod();
        $salesChannelContext = Generator::generateSalesChannelContext(
            salesChannel: $this->getSalesChannel($paymentMethod),
            currency: $this->getCurrency(),
            country: $this->getCountry(),
            paymentMethod: $paymentMethod,
            shippingMethod: $this->getShippingMethod()
        );
        $salesChannelContext->assign(['customer' => null]);

        return $salesChannelContext;
    }

    private function getSalesChannelContextWithCart(): SalesChannelContext
    {
        $salesChannelContext = $this->getSalesChannelContext();
        $this->addProductToSalesChannelContext($salesChannelContext);

        return $salesChannelContext;
    }

    private function createSalesChannelContextWithCart(): SalesChannelContext
    {
        $salesChannelContext = $this->getContainer()->get(SalesChannelContextService::class)->get(
            new SalesChannelContextServiceParameters(TestDefaults::SALES_CHANNEL, Uuid::randomHex())
        );
        $this->addProductToSalesChannelContext($salesChannelContext);

        return $salesChannelContext;
    }

    private function addProductToSalesChannelContext(SalesChannelContext $salesChannelContext): void
    {
        $productId = $this->createTestProduct();
        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

        $cartService = $this->getContainer()->get(CartService::class);
        $cart = $cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $cartService->add($cart, $lineItem, $salesChannelContext);
    }

    private function createTestProduct(): string
    {
        $productId = Uuid::randomHex();

        /** @var EntityRepository $productRepository */
        $productRepository = $this->getContainer()->get('product.repository');
        $productRepository->upsert([[
            'id' => $productId,
            'name' => 'PayPal test product',
            'taxId' => $this->getValidTaxId(),
            'price' => [
                [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => '100',
                    'linked' => true,
                    'net' => '90',
                ],
            ],
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => 30,
                ],
            ],
            'productNumber' => $productId,
            'stock' => 100,
        ]], Context::createDefaultContext());

        return $productId;
    }

    private function getSalesChannel(PaymentMethodEntity $otherPaymentMethod): SalesChannelEntity
    {
        $context = Context::createDefaultContext();
        $container = $this->getContainer();
        /** @var EntityRepository $salesChannelRepo */
        $salesChannelRepo = $container->get('sales_channel.repository');
        $paymentMethodUtil = $container->get(PaymentMethodUtil::class);

        $salesChannelId = TestDefaults::SALES_CHANNEL;
        $countryId = $this->getValidCountryId();
        $this->cleanUpDomain();

        $payPalPaymentMethodId = $paymentMethodUtil->getPayPalPaymentMethodId($context);
        static::assertNotNull($payPalPaymentMethodId);

        $salesChannelRepo->update([
            [
                'id' => $salesChannelId,
                'country' => ['id' => $countryId],
                'countries' => [
                    [
                        'id' => $countryId,
                    ],
                ],
                'domains' => [
                    [
                        'url' => 'https://example.com',
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'currencyId' => Defaults::CURRENCY,
                        'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    ],
                ],
                'paymentMethodId' => $otherPaymentMethod->getId(),
                'paymentMethods' => [
                    ['id' => $payPalPaymentMethodId],
                    ['id' => $otherPaymentMethod->getId()],
                ],
            ],
        ], $context);

        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation('domains');
        $criteria->addAssociation('countries');
        $criteria->addAssociation('country');

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $salesChannelRepo->search($criteria, $context)->first();

        if ($salesChannel === null) {
            throw new SalesChannelNotFoundException();
        }

        $paymentMethodIds = $salesChannel->getPaymentMethodIds();
        if ($paymentMethodIds !== null && !\in_array($payPalPaymentMethodId, $paymentMethodIds, true)) {
            throw OrderException::paymentMethodNotAvailable($payPalPaymentMethodId);
        }

        return $salesChannel;
    }

    private function getCurrency(): CurrencyEntity
    {
        /** @var EntityRepository $currencyRepo */
        $currencyRepo = $this->getContainer()->get('currency.repository');

        /** @var CurrencyEntity $currency */
        $currency = $currencyRepo->search(new Criteria(), Context::createDefaultContext())->first();

        return $currency;
    }

    private function getShippingMethod(): ShippingMethodEntity
    {
        /** @var EntityRepository $shippingMethodRepo */
        $shippingMethodRepo = $this->getContainer()->get('shipping_method.repository');

        /** @var ShippingMethodEntity $shippingMethod */
        $shippingMethod = $shippingMethodRepo->search(new Criteria(), Context::createDefaultContext())->first();

        return $shippingMethod;
    }

    private function getCountry(): CountryEntity
    {
        /** @var EntityRepository $countryRepo */
        $countryRepo = $this->getContainer()->get('country.repository');

        /** @var CountryEntity $country */
        $country = $countryRepo->search(new Criteria([$this->getValidCountryId()]), Context::createDefaultContext())->first();

        return $country;
    }

    private function cleanUpDomain(): void
    {
        /** @var EntityRepository $salesChannelDomainRepo */
        $salesChannelDomainRepo = $this->getContainer()->get('sales_channel_domain.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('url', 'https://example.com'));
        $context = Context::createDefaultContext();

        $id = $salesChannelDomainRepo->searchIds($criteria, $context)->firstId();
        if ($id) {
            $salesChannelDomainRepo->delete([['id' => $id]], $context);
        }
    }
}
