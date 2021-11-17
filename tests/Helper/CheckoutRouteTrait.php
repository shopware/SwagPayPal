<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Order\Exception\PaymentMethodNotAvailableException;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Exception\SalesChannelNotFoundException;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Util\PaymentMethodUtil;

trait CheckoutRouteTrait
{
    use ServicesTrait;
    use PaymentMethodTrait;

    protected function tearDown(): void
    {
        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $context = Context::createDefaultContext();
        $paymentMethodId = $paymentMethodUtil->getPayPalPaymentMethodId($context);
        if ($paymentMethodId !== null) {
            $this->removePaymentMethodFromDefaultsSalesChannel($paymentMethodId, $context);
        }
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        $paymentMethod = $this->getAvailablePaymentMethod();
        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            null,
            $this->getSalesChannel($paymentMethod),
            $this->getCurrency(),
            null,
            $this->getCountry(),
            null,
            null,
            $paymentMethod,
            $this->getShippingMethod()
        );
        $salesChannelContext->assign(['customer' => null]);

        return $salesChannelContext;
    }

    private function getSalesChannel(PaymentMethodEntity $otherPaymentMethod): SalesChannelEntity
    {
        $context = Context::createDefaultContext();
        $container = $this->getContainer();
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $container->get('sales_channel.repository');
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $container->get('payment_method.repository');
        $paymentMethodUtil = new PaymentMethodUtil($paymentRepository, $salesChannelRepo);

        $salesChannelId = Defaults::SALES_CHANNEL;
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
            throw new PaymentMethodNotAvailableException($payPalPaymentMethodId);
        }

        return $salesChannel;
    }

    private function getCurrency(): CurrencyEntity
    {
        /** @var EntityRepositoryInterface $currencyRepo */
        $currencyRepo = $this->getContainer()->get('currency.repository');

        return $currencyRepo->search(new Criteria(), Context::createDefaultContext())->first();
    }

    private function getShippingMethod(): ShippingMethodEntity
    {
        /** @var EntityRepositoryInterface $shippingMethodRepo */
        $shippingMethodRepo = $this->getContainer()->get('shipping_method.repository');

        return $shippingMethodRepo->search(new Criteria(), Context::createDefaultContext())->first();
    }

    private function getCountry(): CountryEntity
    {
        /** @var EntityRepositoryInterface $shippingMethodRepo */
        $shippingMethodRepo = $this->getContainer()->get('country.repository');

        return $shippingMethodRepo->search(new Criteria([$this->getValidCountryId()]), Context::createDefaultContext())->first();
    }

    private function cleanUpDomain(): void
    {
        /** @var EntityRepositoryInterface $salesChannelDomainRepo */
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
