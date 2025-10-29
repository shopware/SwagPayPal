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
    schema: 'paypal_agentic_commerce_v1_value_terms_acceptance_value',
    required: ['accepted', 'termsVersions']
)]
class TermsAcceptanceValue extends PayPalApiStruct
{
    /**
     * Whether terms were accepted
     */
    #[OA\Property(type: 'boolean')]
    protected bool $accepted;

    /**
     * Version of terms accepted
     */
    #[OA\Property(type: 'string')]
    protected string $termsVersions;

    /**
     * When terms were accepted
     */
    #[OA\Property(type: 'string')]
    protected ?string $acceptanceDate = null;

    /**
     * IP address of acceptance
     */
    #[OA\Property(type: 'string')]
    protected ?string $ipAddress = null;

    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted): void
    {
        $this->accepted = $accepted;
    }

    public function getTermsVersions(): string
    {
        return $this->termsVersions;
    }

    public function setTermsVersions(string $termsVersions): void
    {
        $this->termsVersions = $termsVersions;
    }

    public function getAcceptanceDate(): ?string
    {
        return $this->acceptanceDate;
    }

    public function setAcceptanceDate(?string $acceptanceDate): void
    {
        $this->acceptanceDate = $acceptanceDate;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }
}
