<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1\Builder;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\Struct\V1\Referral\MetaData;
use Swag\PayPal\AgenticCommerce\Struct\V1\ResolutionOption;

#[Package('checkout')]
final class MetaDataBuilder
{
    public function __construct(
        private readonly ResolutionOption $resolutionOption,
        private readonly ResolutionBuilder $resolutionBuilder,
    ) {
        if (!$this->resolutionOption->isset('metadata')) {
            $this->resolutionOption->setMetadata(new MetaData());
        }
    }

    public function withCostImpact(string $costImpact): self
    {
        $this->resolutionOption->getMetadata()->setCostImpact($costImpact);

        return $this;
    }

    public function withPriority(string $priority): self
    {
        $this->resolutionOption->getMetadata()->setPriority($priority);

        return $this;
    }

    public function withWaist(string $waist): self
    {
        $this->resolutionOption->getMetadata()->setWaist($waist);

        return $this;
    }

    public function withAutoApplicable(bool $autoApplicable): self
    {
        $this->resolutionOption->getMetadata()->setAutoApplicable($autoApplicable);

        return $this;
    }

    public function withEstimatedTime(string $estimatedTime): self
    {
        $this->resolutionOption->getMetadata()->setEstimatedTime($estimatedTime);

        return $this;
    }

    public function withRedirectRequired(bool $redirectRequired): self
    {
        $this->resolutionOption->getMetadata()->setRedirectRequired($redirectRequired);

        return $this;
    }

    public function end(): ResolutionBuilder
    {
        return $this->resolutionBuilder;
    }
}
