<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\State;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeCollection;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
class PosStateService
{
    /**
     * @internal
     *
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     * @param EntityRepository<SalesChannelTypeCollection> $salesChannelTypeRepository
     * @param EntityRepository<ShippingMethodCollection> $shippingRepository
     * @param EntityRepository<PaymentMethodCollection> $paymentMethodRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $salesChannelTypeRepository,
        private readonly EntityRepository $shippingRepository,
        private readonly EntityRepository $paymentMethodRepository,
    ) {
    }

    public function addPosSalesChannelType(Context $context): void
    {
        $this->salesChannelTypeRepository->upsert([
            [
                'id' => SwagPayPal::SALES_CHANNEL_TYPE_POS,
                'iconName' => 'regular-money-bill',
                'screenshotUrls' => [
                    'swagpaypal/administration/static/img/paypal-pos-sales-channel-type-description-family.png',
                    'swagpaypal/administration/static/img/paypal-pos-sales-channel-type-description-kit.png',
                    'swagpaypal/administration/static/img/paypal-pos-sales-channel-type-description-reader.png',
                    'swagpaypal/administration/static/img/paypal-pos-sales-channel-type-description-tap-payment.png',
                ],
                'name' => 'Point of Sale – Zettle by PayPal',
                'manufacturer' => 'Shopware',
                'description' => 'Tools to build your business',
                'descriptionLong' => 'Zettle’s point-of-sale system allows you to accept cash, card or contactless payments. Connect Shopware to Zettle to keep products, stocks and sales in sync – all in one place.',
                'translations' => [
                    'en-GB' => [
                        'name' => 'Point of Sale – Zettle by PayPal',
                        'manufacturer' => 'Shopware',
                        'description' => 'Tools to build your business',
                        'descriptionLong' => 'Zettle’s point-of-sale system allows you to accept cash, card or contactless payments. Connect Shopware to Zettle to keep products, stocks and sales in sync – all in one place.',
                    ],
                    'de-DE' => [
                        'name' => 'Point of Sale – Zettle by PayPal',
                        'manufacturer' => 'Shopware',
                        'description' => 'Tools zum Aufbau Deines Unternehmens',
                        'descriptionLong' => 'Mit Zettles Point-of-Sale-Lösung kannst Du Zahlungen in bar, mit Karte oder kontaktlos entgegennehmen. Verbinde Shopware mit Zettle, um Produkte, Lagerbestände und Verkäufe synchron zu halten - Alles an einem Ort.',
                    ],
                ],
            ],
        ], $context);
    }

    public function removePosSalesChannelType(Context $context): void
    {
        $this->salesChannelTypeRepository->delete([['id' => SwagPayPal::SALES_CHANNEL_TYPE_POS]], $context);
    }

    public function posSalesChannelsExists(Context $context): bool
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));

        return (bool) $this->salesChannelRepository->searchIds($criteria, $context)->getTotal();
    }

    public function removePosDefaultEntities(Context $context): void
    {
        $this->shippingRepository->delete([['id' => InformationDefaultService::POS_SHIPPING_METHOD_ID]], $context);

        $paymentMethodId = $this->paymentMethodRepository->searchIds(new Criteria([InformationDefaultService::POS_PAYMENT_METHOD_ID]), $context)->firstId();
        if ($paymentMethodId === null) {
            return;
        }

        $this->paymentMethodRepository->update([[
            'id' => InformationDefaultService::POS_PAYMENT_METHOD_ID,
            'pluginId' => null,
        ]], $context);
        $this->paymentMethodRepository->delete([['id' => InformationDefaultService::POS_PAYMENT_METHOD_ID]], $context);
    }

    public function setPosSalesChannelState(bool $active, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        $salesChannels = $this->salesChannelRepository->search($criteria, $context);

        $updateData = \array_values(\array_map(
            static fn (SalesChannelEntity $salesChannel) => [
                'id' => $salesChannel->getId(),
                'active' => $active,
            ],
            $salesChannels->getElements(),
        ));

        $this->salesChannelRepository->update($updateData, $context);
    }
}
