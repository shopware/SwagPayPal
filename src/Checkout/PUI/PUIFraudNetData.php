<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
class PUIFraudNetData extends Struct
{
    protected string $sessionIdentifier;

    protected string $websiteIdentifier;

    protected string $paymentMethodId;

    protected bool $sandbox;

    public function getSessionIdentifier(): string
    {
        return $this->sessionIdentifier;
    }

    public function setSessionIdentifier(string $sessionIdentifier): void
    {
        $this->sessionIdentifier = $sessionIdentifier;
    }

    public function getWebsiteIdentifier(): string
    {
        return $this->websiteIdentifier;
    }

    public function setWebsiteIdentifier(string $websiteIdentifier): void
    {
        $this->websiteIdentifier = $websiteIdentifier;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function setSandbox(bool $sandbox): void
    {
        $this->sandbox = $sandbox;
    }
}
