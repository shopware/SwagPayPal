<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\MerchandizeDisputeProperties;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_extensions")
 */
class Extensions extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     * @OA\Property(type="boolean")
     */
    protected $merchantContacted;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $merchantContactedOutcome;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $merchantContactedTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $merchantContactedMode;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $buyerContactedTime;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $buyerContactedChannel;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var BillingDisputeProperties
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions_billing_dispute_properties")
     */
    protected $billingDisputeProperties;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var MerchandizeDisputeProperties
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_disputes_extensions_merchandize_dispute_properties")
     */
    protected $merchandizeDisputeProperties;

    public function isMerchantContacted(): bool
    {
        return $this->merchantContacted;
    }

    public function setMerchantContacted(bool $merchantContacted): void
    {
        $this->merchantContacted = $merchantContacted;
    }

    public function getMerchantContactedOutcome(): string
    {
        return $this->merchantContactedOutcome;
    }

    public function setMerchantContactedOutcome(string $merchantContactedOutcome): void
    {
        $this->merchantContactedOutcome = $merchantContactedOutcome;
    }

    public function getMerchantContactedTime(): string
    {
        return $this->merchantContactedTime;
    }

    public function setMerchantContactedTime(string $merchantContactedTime): void
    {
        $this->merchantContactedTime = $merchantContactedTime;
    }

    public function getMerchantContactedMode(): string
    {
        return $this->merchantContactedMode;
    }

    public function setMerchantContactedMode(string $merchantContactedMode): void
    {
        $this->merchantContactedMode = $merchantContactedMode;
    }

    public function getBuyerContactedTime(): string
    {
        return $this->buyerContactedTime;
    }

    public function setBuyerContactedTime(string $buyerContactedTime): void
    {
        $this->buyerContactedTime = $buyerContactedTime;
    }

    public function getBuyerContactedChannel(): string
    {
        return $this->buyerContactedChannel;
    }

    public function setBuyerContactedChannel(string $buyerContactedChannel): void
    {
        $this->buyerContactedChannel = $buyerContactedChannel;
    }

    public function getBillingDisputeProperties(): BillingDisputeProperties
    {
        return $this->billingDisputeProperties;
    }

    public function setBillingDisputeProperties(BillingDisputeProperties $billingDisputeProperties): void
    {
        $this->billingDisputeProperties = $billingDisputeProperties;
    }

    public function getMerchandizeDisputeProperties(): MerchandizeDisputeProperties
    {
        return $this->merchandizeDisputeProperties;
    }

    public function setMerchandizeDisputeProperties(MerchandizeDisputeProperties $merchandizeDisputeProperties): void
    {
        $this->merchandizeDisputeProperties = $merchandizeDisputeProperties;
    }
}
