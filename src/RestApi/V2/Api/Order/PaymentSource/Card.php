<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\AuthenticationResult;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payment_source_card")
 */
#[Package('checkout')]
class Card extends AbstractPaymentSource
{
    /**
     * @OA\Property(type="string")
     */
    protected string $lastDigits;

    /**
     * @OA\Property(type="string")
     */
    protected string $brand;

    /**
     * @OA\Property(type="string")
     */
    protected string $type;

    /**
     * @OA\Property(type="swag_paypal_v2_order_payment_source_card_authentication_result", nullable=true)
     */
    protected ?AuthenticationResult $authenticationResult = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_common_attributes")
     */
    protected ?Attributes $attributes = null;

    public function getLastDigits(): string
    {
        return $this->lastDigits;
    }

    public function setLastDigits(string $lastDigits): void
    {
        $this->lastDigits = $lastDigits;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getAuthenticationResult(): ?AuthenticationResult
    {
        return $this->authenticationResult;
    }

    public function setAuthenticationResult(?AuthenticationResult $authenticationResult): void
    {
        $this->authenticationResult = $authenticationResult;
    }

    public function getAttributes(): ?Attributes
    {
        return $this->attributes;
    }

    public function setAttributes(?Attributes $attributes): void
    {
        $this->attributes = $attributes;
    }
}
