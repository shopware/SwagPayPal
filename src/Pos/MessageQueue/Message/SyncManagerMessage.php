<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Message;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class SyncManagerMessage extends AbstractSyncMessage
{
    /**
     * @var string[]
     */
    protected array $steps;

    protected int $currentStep;

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
}
