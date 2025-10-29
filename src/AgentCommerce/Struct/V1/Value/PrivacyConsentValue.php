<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Value;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_value_privacy_consent_value',
    required: ['consented']
)]
class PrivacyConsentValue extends PayPalApiStruct
{
    public const CONSENT_TYPE__DATA_PROCESSING = 'data_processing';
    public const CONSENT_TYPE__MARKETING = 'marketing';
    public const CONSENT_TYPE__THIRD_PARTY_SHARING = 'third_party_sharing';
    public const CONSENT_TYPE__ANALYTICS = 'analytics';

    /**
     * Whether privacy policy was consented to
     */
    #[OA\Property(type: 'boolean')]
    protected bool $consented;

    /**
     * Types of consent given
     *
     * @var string[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
        enum: [self::CONSENT_TYPE__ANALYTICS, self::CONSENT_TYPE__THIRD_PARTY_SHARING, self::CONSENT_TYPE__DATA_PROCESSING, self::CONSENT_TYPE__MARKETING]
    )]
    protected ?array $consentTypes = null;

    /**
     * Privacy policy version
     */
    #[OA\Property(type: 'string')]
    protected ?string $policyVersion = null;

    /**
     * When consent was given
     */
    #[OA\Property(type: 'string')]
    protected ?string $consentDate = null;

    public function isConsented(): bool
    {
        return $this->consented;
    }

    public function setConsented(bool $consented): void
    {
        $this->consented = $consented;
    }

    /**
     * @return ?string[]
     */
    public function getConsentTypes(): ?array
    {
        return $this->consentTypes;
    }

    /**
     * @param ?string[] $consentTypes
     */
    public function setConsentTypes(?array $consentTypes): void
    {
        $this->consentTypes = $consentTypes;
    }

    public function addConsentType(string $consentType): void
    {
        if (!\in_array($consentType, [self::CONSENT_TYPE__ANALYTICS, self::CONSENT_TYPE__THIRD_PARTY_SHARING, self::CONSENT_TYPE__DATA_PROCESSING, self::CONSENT_TYPE__MARKETING], true)) {
            throw new \InvalidArgumentException(\sprintf('Consent type "%s" is not valid.', $consentType));
        }

        $this->consentTypes[] = $consentType;
    }

    public function getPolicyVersion(): ?string
    {
        return $this->policyVersion;
    }

    public function setPolicyVersion(?string $policyVersion): void
    {
        $this->policyVersion = $policyVersion;
    }

    public function getConsentDate(): ?string
    {
        return $this->consentDate;
    }

    public function setConsentDate(?string $consentDate): void
    {
        $this->consentDate = $consentDate;
    }
}
