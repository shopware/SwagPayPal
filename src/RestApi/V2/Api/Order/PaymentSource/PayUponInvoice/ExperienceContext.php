<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_pay_upon_invoice_experience_context")
 */
class ExperienceContext extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $locale;

    /**
     * @OA\Property(type="string")
     */
    protected string $brandName;

    /**
     * @OA\Property(type="string")
     */
    protected string $logoUrl;

    /**
     * @var string[]
     * @OA\Property(type="array", items="string")
     */
    protected array $customerServiceInstructions;

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): void
    {
        $this->brandName = $brandName;
    }

    public function getLogoUrl(): string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(string $logoUrl): void
    {
        $this->logoUrl = $logoUrl;
    }

    /**
     * @return string[]
     */
    public function getCustomerServiceInstructions(): array
    {
        return $this->customerServiceInstructions;
    }

    /**
     * @param string[] $customerServiceInstructions
     */
    public function setCustomerServiceInstructions(array $customerServiceInstructions): void
    {
        $this->customerServiceInstructions = $customerServiceInstructions;
    }
}
