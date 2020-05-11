<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Service\Converter;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\IZettle\Api\Product\Variant;
use Swag\PayPal\IZettle\Api\Product\Variant\Option;

class VariantConverter
{
    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    /**
     * @var PriceConverter
     */
    private $priceConverter;

    public function __construct(
        UuidConverter $uuidConverter,
        PriceConverter $priceConverter
    ) {
        $this->uuidConverter = $uuidConverter;
        $this->priceConverter = $priceConverter;
    }

    public function convert(ProductEntity $shopwareVariant, ?CurrencyEntity $currency): Variant
    {
        $variant = new Variant();

        $uuid = $shopwareVariant->getId();
        if ($shopwareVariant->getParentId() === null) {
            $uuid = $this->uuidConverter->incrementUuid($uuid);
        }
        $variant->setUuid($this->uuidConverter->convertUuidToV1($uuid));

        $variant->setName((string) ($shopwareVariant->getTranslation('name') ?? $shopwareVariant->getName()));
        $variant->setDescription((string) ($shopwareVariant->getTranslation('description') ?? $shopwareVariant->getDescription()));
        $variant->setSku($shopwareVariant->getProductNumber());

        $barcode = $shopwareVariant->getEan();
        if ($barcode !== null) {
            $variant->setBarcode($barcode);
        }

        if ($currency !== null) {
            $price = $shopwareVariant->getCurrencyPrice($currency->getId());
            if ($price !== null) {
                $variant->setPrice($this->priceConverter->convert($price, $currency));
            }
        }

        $shopwareOptions = $shopwareVariant->getOptions();
        if ($shopwareOptions && $shopwareOptions->count()) {
            foreach ($shopwareOptions as $shopwareOption) {
                $group = $shopwareOption->getGroup();
                if ($group !== null) {
                    $option = new Option();
                    $option->setName($group->getTranslation('name') ?? $group->getName());
                    $option->setValue($shopwareOption->getTranslation('name') ?? $shopwareOption->getName());
                    $variant->addOption($option);
                }
            }
        }

        return $variant;
    }
}
