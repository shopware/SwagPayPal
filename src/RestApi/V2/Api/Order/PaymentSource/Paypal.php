<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Common\Name;
use Swag\PayPal\RestApi\V2\Api\Common\PhoneNumber;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Phone;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payment_source_paypal")
 */
#[Package('checkout')]
class Paypal extends AbstractPaymentSource implements VaultablePaymentSourceInterface
{
    /**
     * @OA\Property(type="string")
     */
    protected string $emailAddress;

    /**
     * @OA\Property(type="string")
     */
    protected string $accountId;

    /**
     * @OA\Property(type="string")
     */
    protected string $billingAgreementId;

    /**
     * @OA\Property(type="string")
     */
    protected string $vaultId;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_name")
     */
    protected Name $name;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_phone_number")
     */
    protected ?PhoneNumber $phoneNumber = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_address")
     */
    protected Address $address;

    /**
     * @OA\Property(type="string")
     */
    protected string $birthDate;

    /**
     * @OA\Property(type="string")
     */
    protected string $phoneType;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_common_attributes")
     */
    protected ?Attributes $attributes = null;

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function setAccountId(string $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function getBillingAgreementId(): string
    {
        return $this->billingAgreementId;
    }

    public function setBillingAgreementId(string $billingAgreementId): void
    {
        $this->billingAgreementId = $billingAgreementId;
    }

    public function getVaultId(): string
    {
        return $this->vaultId;
    }

    public function setVaultId(string $vaultId): void
    {
        $this->vaultId = $vaultId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): void
    {
        $this->name = $name;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?PhoneNumber $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function getBirthDate(): string
    {
        return $this->birthDate;
    }

    public function setBirthDate(string $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function getPhoneType(): string
    {
        return $this->phoneType;
    }

    public function setPhoneType(string $phoneType): void
    {
        $this->phoneType = $phoneType;
    }

    public function getAttributes(): ?Attributes
    {
        return $this->attributes;
    }

    public function setAttributes(?Attributes $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getVaultIdentifier(): string
    {
        return $this->getEmailAddress();
    }
}
