<?php declare(strict_types=1);

/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Money;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_shipping_option')]
#[Package('checkout')]
class ShippingOption extends PayPalApiStruct
{
    public const TYPE_SHIPPING = 'SHIPPING';

    public const TYPE_PICKUP = 'PICKUP';

    #[OA\Property(type: 'string')]
    protected string $id;

    #[OA\Property(type: 'string')]
    protected string $label;

    #[OA\Property(ref: Money::class)]
    protected Money $amount;

    /**
     * @var self::TYPE_*
     */
    #[OA\Property(type: 'string', enum: [self::TYPE_SHIPPING, self::TYPE_PICKUP])]
    protected string $type = self::TYPE_SHIPPING;

    #[OA\Property(type: 'boolean')]
    protected bool $selected = false;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function setAmount(Money $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return self::TYPE_*
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param self::TYPE_* $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): void
    {
        $this->selected = $selected;
    }
}
