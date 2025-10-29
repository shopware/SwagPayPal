<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_applied_coupon')]
class AppliedCoupon extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected ?string $code = null;

    #[OA\Property(type: 'string')]
    protected ?string $description = null;

    #[OA\Property(ref: Money::class)]
    protected ?Money $discountAmount = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDiscountAmount(): ?Money
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?Money $discountAmount): void
    {
        $this->discountAmount = $discountAmount;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
