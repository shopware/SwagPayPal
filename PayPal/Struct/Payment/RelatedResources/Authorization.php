<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\RelatedResources;

class Authorization extends RelatedResource
{
    /**
     * @var string
     */
    private $paymentMode;

    /**
     * @var string
     */
    private $protectionEligibility;

    /**
     * @var string
     */
    private $protectionEligibilityType;

    /**
     * @var string
     */
    private $validUntil;

    public function getPaymentMode(): string
    {
        return $this->paymentMode;
    }

    public function setPaymentMode(string $paymentMode): void
    {
        $this->paymentMode = $paymentMode;
    }

    public function getProtectionEligibility(): string
    {
        return $this->protectionEligibility;
    }

    public function setProtectionEligibility(string $protectionEligibility): void
    {
        $this->protectionEligibility = $protectionEligibility;
    }

    public function getProtectionEligibilityType(): string
    {
        return $this->protectionEligibilityType;
    }

    public function setProtectionEligibilityType(string $protectionEligibilityType): void
    {
        $this->protectionEligibilityType = $protectionEligibilityType;
    }

    public function getValidUntil(): string
    {
        return $this->validUntil;
    }

    public function setValidUntil(string $validUntil): void
    {
        $this->validUntil = $validUntil;
    }

    public static function fromArray(array $data): Authorization
    {
        $result = new self();
        $result->prepare($result, $data, ResourceType::AUTHORIZATION);

        if (array_key_exists('payment_mode', $data)) {
            $result->setPaymentMode($data['payment_mode']);
        }
        if (array_key_exists('protection_eligibility', $data)) {
            $result->setProtectionEligibility($data['protection_eligibility']);
        }
        if (array_key_exists('protection_eligibility_type', $data)) {
            $result->setProtectionEligibilityType($data['protection_eligibility_type']);
        }
        if (array_key_exists('valid_until', $data)) {
            $result->setValidUntil($data['valid_until']);
        }

        return $result;
    }
}
