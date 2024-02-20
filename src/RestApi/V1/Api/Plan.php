<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Plan\BillingCycle;
use Swag\PayPal\RestApi\V1\Api\Plan\BillingCycleCollection;
use Swag\PayPal\RestApi\V1\Api\Plan\PaymentPreferences;
use Swag\PayPal\RestApi\V1\Api\Plan\Taxes;

/**
 * @codeCoverageIgnore
 *
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
#[OA\Schema(schema: 'swag_paypal_v1_plan')]
#[Package('checkout')]
class Plan extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $productId;

    #[OA\Property(type: 'string')]
    protected string $name;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $description = null;

    #[OA\Property(type: 'string')]
    protected string $status;

    #[OA\Property(type: 'array', items: new OA\Items(ref: BillingCycle::class))]
    protected BillingCycleCollection $billingCycles;

    #[OA\Property(ref: PaymentPreferences::class)]
    protected PaymentPreferences $paymentPreferences;

    #[OA\Property(ref: Taxes::class)]
    protected Taxes $taxes;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getBillingCycles(): BillingCycleCollection
    {
        return $this->billingCycles;
    }

    public function setBillingCycles(BillingCycleCollection $billingCycles): void
    {
        $this->billingCycles = $billingCycles;
    }

    public function getPaymentPreferences(): PaymentPreferences
    {
        return $this->paymentPreferences;
    }

    public function setPaymentPreferences(PaymentPreferences $paymentPreferences): void
    {
        $this->paymentPreferences = $paymentPreferences;
    }

    public function getTaxes(): Taxes
    {
        return $this->taxes;
    }

    public function setTaxes(Taxes $taxes): void
    {
        $this->taxes = $taxes;
    }
}
