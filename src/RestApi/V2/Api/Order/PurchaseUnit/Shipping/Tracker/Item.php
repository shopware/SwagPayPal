<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Shipping\Tracker;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_shipping_tracker_item')]
#[Package('checkout')]
class Item extends PayPalApiStruct
{
    public const MAX_LENGTH_NAME = 120;
    public const MAX_LENGTH_SKU = 127;
    public const MAX_LENGTH_URL = 2048;

    #[OA\Property(type: 'string')]
    protected string $name;

    #[OA\Property(type: 'integer')]
    protected int $quantity;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $sku = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $url = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $image_url = null;

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setName(string $name): void
    {
        if (\mb_strlen($name) > self::MAX_LENGTH_NAME) {
            throw new \LengthException(
                \sprintf('%s::$name must not be longer than %s characters', self::class, self::MAX_LENGTH_NAME)
            );
        }

        $this->name = $name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int|string $quantity): void
    {
        $this->quantity = (int) $quantity;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setSku(?string $sku): void
    {
        if ($sku !== null && \mb_strlen($sku) > self::MAX_LENGTH_SKU) {
            throw new \LengthException(
                \sprintf('%s::$sku must not be longer than %s characters', self::class, self::MAX_LENGTH_SKU)
            );
        }

        $this->sku = $sku;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setUrl(?string $url): void
    {
        if ($url !== null && \mb_strlen($url) > self::MAX_LENGTH_URL) {
            throw new \LengthException(
                \sprintf('%s::$url must not be longer than %s characters', self::class, self::MAX_LENGTH_URL)
            );
        }

        $this->url = $url;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setImageUrl(?string $image_url): void
    {
        if ($image_url !== null && \mb_strlen($image_url) > self::MAX_LENGTH_URL) {
            throw new \LengthException(
                \sprintf('%s::$image_url must not be longer than %s characters', self::class, self::MAX_LENGTH_URL)
            );
        }

        $this->image_url = $image_url;
    }
}
