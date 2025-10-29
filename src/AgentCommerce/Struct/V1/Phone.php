<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_phone',
    required: ['countryCode', 'nationalNumber']
)]
class Phone extends PayPalApiStruct
{
    private const PHONE_NUMBER_REGEX = '/\+(\d{1,3})\s(\d{1,14})(-?(\d{1,15}))?/';

    /**
     * The country calling code (CC), in its canonical international E.164 numbering plan format.
     * The combined length of the CC and the national number must not be greater than 15 digits.
     * The national number consists of a national destination code (NDC) and subscriber number (SN)
     */
    #[OA\Property(
        type: 'string',
        maxLength: 3,
        minLength: 1,
        pattern: '^[0-9]{1,3}?$'
    )]
    protected string $countryCode;

    /**
     * The national number, in its canonical international E.164 numbering plan format.
     * The combined length of the country calling code (CC) and the national number must not be greater than 15 digits.
     * The national number consists of a national destination code (NDC) and subscriber number (SN).
     */
    #[OA\Property(
        type: 'string',
        maxLength: 14,
        minLength: 1,
        pattern: '^[0-9]{1,14}?$'
    )]
    protected string $nationalNumber;

    /**
     * The extension number
     */
    #[OA\Property(
        type: 'string',
        maxLength: 15,
        minLength: 1,
        pattern: '^[0-9]{1,15}?$'
    )]
    protected ?string $extensionNumber = null;

    public static function isValidPhoneNumber(string $phoneNumber): bool
    {
        return (bool) preg_match(self::PHONE_NUMBER_REGEX, $phoneNumber);
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getNationalNumber(): string
    {
        return $this->nationalNumber;
    }

    public function setNationalNumber(string $nationalNumber): void
    {
        $this->nationalNumber = $nationalNumber;
    }

    public function getExtensionNumber(): ?string
    {
        return $this->extensionNumber;
    }

    public function setExtensionNumber(?string $extensionNumber): void
    {
        $this->extensionNumber = $extensionNumber;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }

    public function getFullPhoneNumber(): string
    {
        $number = '+' . $this->countryCode . ' ' . $this->nationalNumber;

        if ($this->extensionNumber) {
            $number .= '-' . $this->extensionNumber;
        }

        return $number;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        if (!preg_match(self::PHONE_NUMBER_REGEX, $phoneNumber, $matches)) {
            throw new \InvalidArgumentException('Invalid phone number: ' . $phoneNumber);
        }

        $this->countryCode = $matches[1];
        $this->nationalNumber = $matches[2];
        $this->extensionNumber = $matches[4] ?? null;
    }
}
