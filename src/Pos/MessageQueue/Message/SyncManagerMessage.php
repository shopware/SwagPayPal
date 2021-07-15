<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message;

class SyncManagerMessage extends AbstractSyncMessage
{
    /**
     * @var string[]
     */
    private array $steps;

    private int $currentStep;

    private int $lastMessageCount = 0;

    private int $messageRetries = 0;

    /**
     * @return string[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @param string[] $steps
     */
    public function setSteps(array $steps): void
    {
        $this->steps = $steps;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function setCurrentStep(int $currentStep): void
    {
        $this->currentStep = $currentStep;
    }

    public function getLastMessageCount(): int
    {
        return $this->lastMessageCount;
    }

    public function setLastMessageCount(int $lastMessageCount): void
    {
        $this->lastMessageCount = $lastMessageCount;
    }

    public function getMessageRetries(): int
    {
        return $this->messageRetries;
    }

    public function setMessageRetries(int $messageRetries): void
    {
        $this->messageRetries = $messageRetries;
    }
}
