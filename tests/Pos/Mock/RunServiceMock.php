<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Run\RunService;

/**
 * @internal
 */
#[Package('checkout')]
class RunServiceMock extends RunService
{
    private int $messageCount = 0;

    public function setMessageCount(int $messageCount, string $runId, Context $context): void
    {
        $this->messageCount = $messageCount;
    }

    public function decrementMessageCount(string $runId): void
    {
        --$this->messageCount;
    }

    public function getMessageCount(): int
    {
        return $this->messageCount;
    }
}
