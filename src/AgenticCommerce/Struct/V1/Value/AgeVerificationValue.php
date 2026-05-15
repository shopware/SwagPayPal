<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1\Value;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_value_age_verification_value',
    required: ['confirmed']
)]
class AgeVerificationValue
{
    public const METHOD__SELF_DECLARATION = 'self_declaration';
    public const METHOD__ID_VERIFICATION = 'id_verification';
    public const METHOD__THIRD_PARTY = 'third_party';

    /**
     * Whether age verification was confirmed
     */
    #[OA\Property(type: 'boolean')]
    protected bool $confirmed = false;

    /**
     * Method used for age verification
     */
    #[OA\Property(
        type: 'string',
        enum: [self::METHOD__SELF_DECLARATION, self::METHOD__ID_VERIFICATION, self::METHOD__THIRD_PARTY],
    )]
    protected ?string $verificationMethod = null;

    /**
     * When verification was completed
     */
    #[OA\Property(type: 'string')]
    protected ?string $verificationDate = null;

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }

    public function getVerificationMethod(): ?string
    {
        return $this->verificationMethod;
    }

    public function setVerificationMethod(?string $verificationMethod): void
    {
        if (!\in_array($verificationMethod, [self::METHOD__SELF_DECLARATION, self::METHOD__ID_VERIFICATION, self::METHOD__THIRD_PARTY], true)) {
            throw new \InvalidArgumentException(\sprintf('Verification Method "%s" is not valid.', $verificationMethod));
        }

        $this->verificationMethod = $verificationMethod;
    }

    public function getVerificationDate(): ?string
    {
        return $this->verificationDate;
    }

    public function setVerificationDate(?string $verificationDate): void
    {
        $this->verificationDate = $verificationDate;
    }
}
