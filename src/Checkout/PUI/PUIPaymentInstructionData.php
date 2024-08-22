<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;

#[Package('checkout')]
class PUIPaymentInstructionData extends Struct
{
    protected string $pollingUrl;

    protected string $finishUrl;

    protected string $errorUrl;

    protected ?PayUponInvoice $paymentInstructions = null;

    protected string $paymentMethodId;

    public function getPollingUrl(): string
    {
        return $this->pollingUrl;
    }

    public function setPollingUrl(string $pollingUrl): void
    {
        $this->pollingUrl = $pollingUrl;
    }

    public function getFinishUrl(): string
    {
        return $this->finishUrl;
    }

    public function setFinishUrl(string $finishUrl): void
    {
        $this->finishUrl = $finishUrl;
    }

    public function getErrorUrl(): string
    {
        return $this->errorUrl;
    }

    public function setErrorUrl(string $errorUrl): void
    {
        $this->errorUrl = $errorUrl;
    }

    public function getPaymentInstructions(): ?PayUponInvoice
    {
        return $this->paymentInstructions;
    }

    public function setPaymentInstructions(?PayUponInvoice $paymentInstructions): void
    {
        $this->paymentInstructions = $paymentInstructions;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }
}
