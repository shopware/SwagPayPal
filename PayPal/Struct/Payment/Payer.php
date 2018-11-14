<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment;

use SwagPayPal\PayPal\Struct\Payment\Payer\PayerInfo;

class Payer
{
    /**
     * The payment of the request that is expected by PayPal
     *
     * @var string
     */
    private $paymentMethod = 'paypal';

    /**
     * @var string
     */
    private $status;

    /**
     * @var PayerInfo
     */
    private $payerInfo;

    /**
     * @var string
     */
    private $externalSelectedFundingInstrumentType;

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getPayerInfo(): PayerInfo
    {
        return $this->payerInfo;
    }

    public function setPayerInfo(PayerInfo $payerInfo): void
    {
        $this->payerInfo = $payerInfo;
    }

    public function getExternalSelectedFundingInstrumentType(): ?string
    {
        return $this->externalSelectedFundingInstrumentType;
    }

    public function setExternalSelectedFundingInstrumentType(string $externalSelectedFundingInstrumentType): void
    {
        $this->externalSelectedFundingInstrumentType = $externalSelectedFundingInstrumentType;
    }

    public static function fromArray(array $data = []): Payer
    {
        $result = new self();

        $result->setPaymentMethod($data['payment_method']);

        if (array_key_exists('payer_info', $data)) {
            $result->setPayerInfo(PayerInfo::fromArray($data['payer_info']));
        }

        if (array_key_exists('status', $data)) {
            $result->setStatus($data['status']);
        }

        if (array_key_exists('external_selected_funding_instrument_type', $data)) {
            $result->setExternalSelectedFundingInstrumentType($data['external_selected_funding_instrument_type']);
        }

        return $result;
    }

    public function toArray(): array
    {
        $result = [
            'payment_method' => $this->getPaymentMethod(),
            'status' => $this->getStatus(),
            'external_selected_funding_instrument_type' => $this->getExternalSelectedFundingInstrumentType(),
        ];

        if ($this->payerInfo !== null) {
            $result['payer_info'] = $this->payerInfo->toArray();
        }

        return $result;
    }
}
