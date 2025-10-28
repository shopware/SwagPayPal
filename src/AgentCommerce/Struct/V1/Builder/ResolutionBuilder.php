<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Builder;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Struct\V1\ResolutionOption;
use Swag\PayPal\AgentCommerce\Struct\V1\ResolutionOptionCollection;
use Swag\PayPal\AgentCommerce\Struct\V1\ValidationIssue;

#[Package('checkout')]
final class ResolutionBuilder
{
    private ResolutionOption $resolution;

    public function __construct(
        private readonly ValidationIssue $issue,
        private readonly ValidationIssueBuilder $validationIssueBuilder,
    ) {
        if (!$this->issue->isset('resolutionOptions')) {
            $this->issue->setResolutionOptions(new ResolutionOptionCollection());
        }

        $this->resolution = new ResolutionOption();
        $this->issue->getResolutionOptions()->add($this->resolution);
    }

    public function withAction(string $action): self
    {
        $this->resolution->setAction($action);

        return $this;
    }

    public function withLabel(string $label): self
    {
        $this->resolution->setLabel($label);

        return $this;
    }

    public function withUrl(string $url): self
    {
        $this->resolution->setUrl($url);

        return $this;
    }

    public function withMetadata(): MetaDataBuilder
    {
        return new MetaDataBuilder($this->resolution, $this);
    }

    public function end(): ValidationIssueBuilder
    {
        return $this->validationIssueBuilder;
    }
}
