<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Referral;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_referral_meta_data')]
class MetaData extends Struct
{
    public const PRIORITY__HIGH = 'HIGH';
    public const PRIORITY__MEDIUM = 'MEDIUM';
    public const PRIORITY__LOW = 'LOW';

    #[OA\Property(type: 'string')]
    protected string $costImpact;

    #[OA\Property(type: 'string')]
    protected string $priority;

    #[OA\Property(type: 'string')]
    protected string $waist;

    #[OA\Property(type: 'boolean')]
    protected bool $autoApplicable;

    #[OA\Property(type: 'string')]
    protected string $estimatedTime;

    #[OA\Property(type: 'boolean')]
    protected bool $redirectRequired;

    public function getCostImpact(): string
    {
        return $this->costImpact;
    }

    public function setCostImpact(string $costImpact): void
    {
        $this->costImpact = $costImpact;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): void
    {
        if (!\in_array($priority, [self::PRIORITY__HIGH, self::PRIORITY__MEDIUM, self::PRIORITY__LOW], true)) {
            throw new \InvalidArgumentException('Invalid priority');
        }

        $this->priority = $priority;
    }

    public function getWaist(): string
    {
        return $this->waist;
    }

    public function setWaist(string $waist): void
    {
        $this->waist = $waist;
    }

    public function isAutoApplicable(): bool
    {
        return $this->autoApplicable;
    }

    public function setAutoApplicable(bool $autoApplicable): void
    {
        $this->autoApplicable = $autoApplicable;
    }

    public function getEstimatedTime(): string
    {
        return $this->estimatedTime;
    }

    public function setEstimatedTime(string $estimatedTime): void
    {
        $this->estimatedTime = $estimatedTime;
    }

    public function isRedirectRequired(): bool
    {
        return $this->redirectRequired;
    }

    public function setRedirectRequired(bool $redirectRequired): void
    {
        $this->redirectRequired = $redirectRequired;
    }
}
