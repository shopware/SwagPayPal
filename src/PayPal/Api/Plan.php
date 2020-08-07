<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Plan\BillingCycle;
use Swag\PayPal\PayPal\Api\Plan\PaymentPreferences;
use Swag\PayPal\PayPal\Api\Plan\Taxes;

class Plan extends PayPalStruct
{
    /** @var string */
    protected $productId;

    /** @var string */
    protected $name;

    /** @var ?string */
    protected $description;

    /** @var string */
    protected $status;

    /** @var BillingCycle[] */
    protected $billingCycles = [];

    /** @var PaymentPreferences */
    protected $paymentPreferences;

    /** @var Taxes */
    protected $taxes;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return BillingCycle[]
     */
    public function getBillingCycles(): array
    {
        return $this->billingCycles;
    }

    /**
     * @param BillingCycle[] $billingCycles
     */
    public function setBillingCycles(array $billingCycles): self
    {
        $this->billingCycles = $billingCycles;

        return $this;
    }

    public function getPaymentPreferences(): PaymentPreferences
    {
        return $this->paymentPreferences;
    }

    public function setPaymentPreferences(PaymentPreferences $paymentPreferences): self
    {
        $this->paymentPreferences = $paymentPreferences;

        return $this;
    }

    public function getTaxes(): Taxes
    {
        return $this->taxes;
    }

    public function setTaxes(Taxes $taxes): self
    {
        $this->taxes = $taxes;

        return $this;
    }
}
