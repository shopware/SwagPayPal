<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 *
 * Payment method information for PayPal Cart API. This API is specifically designed for PayPal's shopping cart service, so only PayPal payment methods are supported.
 *
 * Payment Flow:
 *
 * Cart creation generates a payment token (in payment_method.token)
 * Customer completes PayPal approval (Smart Wallet)
 * PayPal provides token and payer_id for checkout
 * Merchant receives PayPal payment confirmation
 *
 * Billing Address Behavior:
 *
 * PayPal handles all billing address collection internally for payment processing
 * Merchants can optionally collect billing addresses for tax calculation and business purposes
 * Billing address in cart is for merchant use cases, not payment requirements
 * Billing addresses are typically available from customer profile data
 *
 * Note: Other payment methods (credit cards, Apple Pay, etc.) would be handled by separate merchant payment systems outside of this PayPal Cart API.
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_payment_method',
    required: ['type']
)]
class PaymentMethod extends PayPalApiStruct
{
    /**
     * Payment method type - only PayPal is supported by this API
     */
    #[OA\Property(
        type: 'string',
        enum: ['paypal']
    )]
    protected string $type = 'paypal';

    /**
     * PayPal payment token from cart creation or customer approval
     */
    #[OA\Property(type: 'string')]
    protected ?string $token = null;

    /**
     * PayPal payer identifier provided after customer approval
     */
    #[OA\Property(type: 'string')]
    protected ?string $payerId = null;

    /**
     * URL used to inform merchant that the PayPal buyer approved the order
     */
    #[OA\Property(type: 'string')]
    protected ?string $approvalUrl = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    public function getPayerId(): ?string
    {
        return $this->payerId;
    }

    public function setPayerId(?string $payerId): void
    {
        $this->payerId = $payerId;
    }

    public function getApprovalUrl(): ?string
    {
        return $this->approvalUrl;
    }

    public function setApprovalUrl(?string $approvalUrl): void
    {
        $this->approvalUrl = $approvalUrl;
    }
}
