<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
class AgentError extends PayPalApiStruct
{
    protected string $name;

    protected int $code;

    protected string $message;

    protected ?string $debugId = null;

    protected ?AgentErrorDetailCollection $details = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getDebugId(): ?string
    {
        return $this->debugId;
    }

    public function setDebugId(?string $debugId): void
    {
        $this->debugId = $debugId;
    }

    public function getDetails(): ?AgentErrorDetailCollection
    {
        return $this->details;
    }

    public function setDetails(?AgentErrorDetailCollection $details): void
    {
        $this->details = $details;
    }
}
