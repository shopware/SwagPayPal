<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\State;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Exception\ExistingPosSalesChannelsException;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
class PosStateService
{
    private EntityRepository $salesChannelRepository;

    private EntityRepository $salesChannelTypeRepository;

    private EntityRepository $shippingRepository;

    private EntityRepository $paymentMethodRepository;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $salesChannelRepository,
        EntityRepository $salesChannelTypeRepository,
        EntityRepository $shippingRepository,
        EntityRepository $paymentMethodRepository,
    ) {
        $this->salesChannelRepository = $salesChannelRepository;
        $this->salesChannelTypeRepository = $salesChannelTypeRepository;
        $this->shippingRepository = $shippingRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function addPosSalesChannelType(Context $context): void
    {
        $this->salesChannelTypeRepository->upsert([
            [
                'id' => SwagPayPal::SALES_CHANNEL_TYPE_POS,
                'iconName' => 'regular-money-bill',
                'screenshotUrls' => [
                    'swagpaypal/static/img/paypal-pos-sales-channel-type-description-family.png',
                    'swagpaypal/static/img/paypal-pos-sales-channel-type-description-kit.png',
                    'swagpaypal/static/img/paypal-pos-sales-channel-type-description-reader.png',
                    'swagpaypal/static/img/paypal-pos-sales-channel-type-description-tap-payment.png',
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

    /**
     * @throws ExistingPosSalesChannelsException
     */
    public function checkPosSalesChannels(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        $result = $this->salesChannelRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            $names = $result->getEntities()->map(function (SalesChannelEntity $item): string {
                return (string) $item->getName();
            });

            throw new ExistingPosSalesChannelsException($result->getTotal(), $names);
        }
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
}
