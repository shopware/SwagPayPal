<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api;

use Swag\PayPal\Pos\Api\Common\PosStruct;

class MerchantInformation extends PosStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $uuid;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $name;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $receiptName;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $city;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $zipCode;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $address;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $addressLine2;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $legalName;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $legalAddress;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $legalZipCode;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $legalCity;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $legalState;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $phoneNumber;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $contactEmail;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $receiptEmail;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $legalEntityType;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $legalEntityNr;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var float
     */
    protected $vatPercentage;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $country;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $language;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $currency;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $created;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $ownerUuid;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var int
     */
    protected $organizationId;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $customerStatus;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     */
    protected $usesVat;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $customerType;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $timeZone;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getReceiptName(): string
    {
        return $this->receiptName;
    }

    public function setReceiptName(string $receiptName): void
    {
        $this->receiptName = $receiptName;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getAddressLine2(): string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(string $addressLine2): void
    {
        $this->addressLine2 = $addressLine2;
    }

    public function getLegalName(): string
    {
        return $this->legalName;
    }

    public function setLegalName(string $legalName): void
    {
        $this->legalName = $legalName;
    }

    public function getLegalAddress(): string
    {
        return $this->legalAddress;
    }

    public function setLegalAddress(string $legalAddress): void
    {
        $this->legalAddress = $legalAddress;
    }

    public function getLegalZipCode(): string
    {
        return $this->legalZipCode;
    }

    public function setLegalZipCode(string $legalZipCode): void
    {
        $this->legalZipCode = $legalZipCode;
    }

    public function getLegalCity(): string
    {
        return $this->legalCity;
    }

    public function setLegalCity(string $legalCity): void
    {
        $this->legalCity = $legalCity;
    }

    public function getLegalState(): string
    {
        return $this->legalState;
    }

    public function setLegalState(string $legalState): void
    {
        $this->legalState = $legalState;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    public function getReceiptEmail(): string
    {
        return $this->receiptEmail;
    }

    public function setReceiptEmail(string $receiptEmail): void
    {
        $this->receiptEmail = $receiptEmail;
    }

    public function getLegalEntityType(): string
    {
        return $this->legalEntityType;
    }

    public function setLegalEntityType(string $legalEntityType): void
    {
        $this->legalEntityType = $legalEntityType;
    }

    public function getLegalEntityNr(): string
    {
        return $this->legalEntityNr;
    }

    public function setLegalEntityNr(string $legalEntityNr): void
    {
        $this->legalEntityNr = $legalEntityNr;
    }

    public function getVatPercentage(): float
    {
        return $this->vatPercentage;
    }

    public function setVatPercentage(float $vatPercentage): void
    {
        $this->vatPercentage = $vatPercentage;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getCreated(): string
    {
        return $this->created;
    }

    public function setCreated(string $created): void
    {
        $this->created = $created;
    }

    public function getOwnerUuid(): string
    {
        return $this->ownerUuid;
    }

    public function setOwnerUuid(string $ownerUuid): void
    {
        $this->ownerUuid = $ownerUuid;
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

    public function setOrganizationId(int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function getCustomerStatus(): string
    {
        return $this->customerStatus;
    }

    public function setCustomerStatus(string $customerStatus): void
    {
        $this->customerStatus = $customerStatus;
    }

    public function isUsesVat(): bool
    {
        return $this->usesVat;
    }

    public function setUsesVat(bool $usesVat): void
    {
        $this->usesVat = $usesVat;
    }

    public function getCustomerType(): string
    {
        return $this->customerType;
    }

    public function setCustomerType(string $customerType): void
    {
        $this->customerType = $customerType;
    }

    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    public function setTimeZone(string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }
}
