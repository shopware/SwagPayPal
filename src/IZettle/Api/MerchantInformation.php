<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class MerchantInformation extends IZettleStruct
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $receiptName;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $zipCode;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $addressLine2;

    /**
     * @var string
     */
    private $legalName;

    /**
     * @var string
     */
    private $legalAddress;

    /**
     * @var string
     */
    private $legalZipCode;

    /**
     * @var string
     */
    private $legalCity;

    /**
     * @var string
     */
    private $legalState;

    /**
     * @var string
     */
    private $phoneNumber;

    /**
     * @var string
     */
    private $contactEmail;

    /**
     * @var string
     */
    private $receiptEmail;

    /**
     * @var string
     */
    private $legalEntityType;

    /**
     * @var string
     */
    private $legalEntityNr;

    /**
     * @var float
     */
    private $vatPercentage;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $created;

    /**
     * @var string
     */
    private $ownerUuid;

    /**
     * @var int
     */
    private $organizationId;

    /**
     * @var string
     */
    private $customerStatus;

    /**
     * @var bool
     */
    private $usesVat;

    /**
     * @var string
     */
    private $customerType;

    /**
     * @var string
     */
    private $timeZone;

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReceiptEmail(): string
    {
        return $this->receiptEmail;
    }

    protected function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    protected function setName(string $name): void
    {
        $this->name = $name;
    }

    protected function setReceiptName(string $receiptName): void
    {
        $this->receiptName = $receiptName;
    }

    protected function setCity(string $city): void
    {
        $this->city = $city;
    }

    protected function setZipCode(string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    protected function setAddress(string $address): void
    {
        $this->address = $address;
    }

    protected function setAddressLine2(string $addressLine2): void
    {
        $this->addressLine2 = $addressLine2;
    }

    protected function setLegalName(string $legalName): void
    {
        $this->legalName = $legalName;
    }

    protected function setLegalAddress(string $legalAddress): void
    {
        $this->legalAddress = $legalAddress;
    }

    protected function setLegalZipCode(string $legalZipCode): void
    {
        $this->legalZipCode = $legalZipCode;
    }

    protected function setLegalCity(string $legalCity): void
    {
        $this->legalCity = $legalCity;
    }

    protected function setLegalState(string $legalState): void
    {
        $this->legalState = $legalState;
    }

    protected function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    protected function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    protected function setReceiptEmail(string $receiptEmail): void
    {
        $this->receiptEmail = $receiptEmail;
    }

    protected function setLegalEntityType(string $legalEntityType): void
    {
        $this->legalEntityType = $legalEntityType;
    }

    protected function setLegalEntityNr(string $legalEntityNr): void
    {
        $this->legalEntityNr = $legalEntityNr;
    }

    protected function setVatPercentage(float $vatPercentage): void
    {
        $this->vatPercentage = $vatPercentage;
    }

    protected function setCountry(string $country): void
    {
        $this->country = $country;
    }

    protected function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    protected function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    protected function setCreated(string $created): void
    {
        $this->created = $created;
    }

    protected function setOwnerUuid(string $ownerUuid): void
    {
        $this->ownerUuid = $ownerUuid;
    }

    protected function setOrganizationId(int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    protected function setCustomerStatus(string $customerStatus): void
    {
        $this->customerStatus = $customerStatus;
    }

    protected function setUsesVat(bool $usesVat): void
    {
        $this->usesVat = $usesVat;
    }

    protected function setCustomerType(string $customerType): void
    {
        $this->customerType = $customerType;
    }

    protected function setTimeZone(string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }
}
