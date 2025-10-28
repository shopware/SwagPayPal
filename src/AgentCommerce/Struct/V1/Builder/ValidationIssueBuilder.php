<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Builder;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Struct\V1\Context\AbstractContext;
use Swag\PayPal\AgentCommerce\Struct\V1\ResolutionOptionCollection;
use Swag\PayPal\AgentCommerce\Struct\V1\ValidationIssue;

#[Package('checkout')]
final class ValidationIssueBuilder
{
    private readonly ValidationIssue $issue;

    public function __construct()
    {
        $this->issue = new ValidationIssue();
    }

    public function build(): ValidationIssue
    {
        return $this->issue;
    }

    public function withCode(string $code): self
    {
        $this->issue->setCode($code);

        return $this;
    }

    public function withType(string $type): self
    {
        $this->issue->setType($type);

        return $this;
    }

    public function withMessage(string $message): self
    {
        $this->issue->setMessage($message);

        return $this;
    }

    public function withUserMessage(string $userMessage): self
    {
        $this->issue->setUserMessage($userMessage);

        return $this;
    }

    public function withItemId(string $itemId): self
    {
        $this->issue->setItemId($itemId);

        return $this;
    }

    public function withField(string $field): self
    {
        $this->issue->setField($field);

        return $this;
    }

    public function withContext(AbstractContext $context): self
    {
        $this->issue->setContext($context);

        return $this;
    }

    public function withResolutionOptions(ResolutionOptionCollection $resolutionOptions): self
    {
        $this->issue->setResolutionOptions($resolutionOptions);

        return $this;
    }

    public function addResolutionOption(): ResolutionBuilder
    {
        return new ResolutionBuilder($this->issue, $this);
    }
}
