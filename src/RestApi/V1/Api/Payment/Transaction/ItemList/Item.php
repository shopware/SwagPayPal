<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_payment_transaction_item_list_item')]
#[Package('checkout')]
class Item extends PayPalApiStruct
{
    public const MAX_LENGTH_NAME = 127;
    public const MAX_LENGTH_SKU = 127;

    #[OA\Property(type: 'string')]
    protected string $name;

    #[OA\Property(type: 'string')]
    protected string $currency;

    #[OA\Property(type: 'string')]
    protected string $price;

    #[OA\Property(type: 'integer')]
    protected int $quantity;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $sku = null;

    #[OA\Property(type: 'string')]
    protected string $tax;

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

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): void
    {
        $this->price = $price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
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

    public function getTax(): string
    {
        return $this->tax;
    }

    public function setTax(string $tax): void
    {
        $this->tax = $tax;
    }
}
