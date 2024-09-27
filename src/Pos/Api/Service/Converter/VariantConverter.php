<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Service\Converter;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\Pos\Api\Product\Variant;
use Swag\PayPal\Pos\Api\Product\Variant\CostPrice;
use Swag\PayPal\Pos\Api\Product\Variant\Option;
use Swag\PayPal\Pos\Sync\Context\ProductContext;

#[Package('checkout')]
class VariantConverter
{
    private UuidConverter $uuidConverter;

    private PriceConverter $priceConverter;

    private PresentationConverter $presentationConverter;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        UuidConverter $uuidConverter,
        PriceConverter $priceConverter,
        PresentationConverter $presentationConverter,
        LoggerInterface $logger,
    ) {
        $this->uuidConverter = $uuidConverter;
        $this->priceConverter = $priceConverter;
        $this->presentationConverter = $presentationConverter;
        $this->logger = $logger;
    }

    public function convert(SalesChannelProductEntity $shopwareVariant, ?CurrencyEntity $currency, ProductContext $productContext): Variant
    {
        $variant = new Variant();

        $uuid = $shopwareVariant->getId();
        if ($shopwareVariant->getParentId() === null) {
            $uuid = $this->uuidConverter->incrementUuid($uuid);
        }
        $variant->setUuid($this->uuidConverter->convertUuidToV1($uuid));

        $variant->setName((string) ($shopwareVariant->getTranslation('name') ?? $shopwareVariant->getName()));
        $variant->setSku($shopwareVariant->getProductNumber());
        $variant->setDescription((string) ($shopwareVariant->getTranslation('description') ?? $shopwareVariant->getDescription()));
        if (\mb_strlen($variant->getDescription()) > 1024) {
            $variant->setDescription(\sprintf('%s...', \mb_substr($variant->getDescription(), 0, 1021)));

            $this->logger->warning(
                'The description of product "{productName}" is too long and will be cut off at 1024 characters.',
                [
                    'productName' => $variant->getName(),
                    'product' => $shopwareVariant,
                ]
            );
        }

        $barcode = $shopwareVariant->getEan();
        if ($barcode !== null) {
            $variant->setBarcode($barcode);
        }

        if ($currency !== null) {
            $price = $shopwareVariant->getCalculatedPrice();
            $variant->setPrice($this->priceConverter->convert($price, $currency));

            $costPrice = $this->getPurchasePrice($shopwareVariant, $currency);
            if ($costPrice !== null) {
                $variant->setCostPrice(CostPrice::convertFromPrice($this->priceConverter->convertFloat($costPrice, $currency)));
            }
        }

        $presentation = $this->presentationConverter->convert($shopwareVariant->getCover(), $productContext);
        if ($presentation !== null) {
            $variant->setPresentation($presentation);
        }

        $shopwareOptions = $shopwareVariant->getOptions();
        if ($shopwareOptions && $shopwareOptions->count()) {
            foreach ($shopwareOptions as $shopwareOption) {
                $group = $shopwareOption->getGroup();
                if ($group !== null) {
                    $option = new Option();
                    $option->setName($group->getTranslation('name') ?? $group->getName() ?? '');
                    $option->setValue($shopwareOption->getTranslation('name') ?? $shopwareOption->getName() ?? '');
                    if ($option->getName() !== '' && $option->getValue() !== '') {
                        $variant->addOption($option);
                    }
                }
            }
        }

        return $variant;
    }

    private function getPurchasePrice(SalesChannelProductEntity $shopwareVariant, CurrencyEntity $currency): ?float
    {
        $purchasePrices = $shopwareVariant->getPurchasePrices();
        if ($purchasePrices === null) {
            return null;
        }

        $purchasePrice = $purchasePrices->get($currency->getId());
        if ($purchasePrice === null) {
            return null;
        }

        return $purchasePrice->getGross();
    }
}
