<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice\BillingAddress;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice\DepositBankDetails;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice\ExperienceContext;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice\Name;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice\Phone;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_pay_upon_invoice")
 */
#[Package('checkout')]
class PayUponInvoice extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_name")
     */
    protected Name $name;

    /**
     * @OA\Property(type="string")
     */
    protected string $email;

    /**
     * @OA\Property(type="string")
     */
    protected string $birthDate;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_phone_number")
     */
    protected Phone $phone;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_address")
     */
    protected BillingAddress $billingAddress;

    /**
     * @OA\Property(type="string")
     */
    protected string $paymentReference;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_pay_upon_invoice_deposit_bank_details")
     */
    protected DepositBankDetails $depositBankDetails;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_pay_upon_invoice_experience_context")
     */
    protected ExperienceContext $experienceContext;

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getBirthDate(): string
    {
        return $this->birthDate;
    }

    public function setBirthDate(string $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function getPhone(): Phone
    {
        return $this->phone;
    }

    public function setPhone(Phone $phone): void
    {
        $this->phone = $phone;
    }

    public function getBillingAddress(): BillingAddress
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(BillingAddress $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getPaymentReference(): string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(string $paymentReference): void
    {
        $this->paymentReference = $paymentReference;
    }

    public function getDepositBankDetails(): DepositBankDetails
    {
        return $this->depositBankDetails;
    }

    public function setDepositBankDetails(DepositBankDetails $depositBankDetails): void
    {
        $this->depositBankDetails = $depositBankDetails;
    }

    public function getExperienceContext(): ExperienceContext
    {
        return $this->experienceContext;
    }

    public function setExperienceContext(ExperienceContext $experienceContext): void
    {
        $this->experienceContext = $experienceContext;
    }
}
