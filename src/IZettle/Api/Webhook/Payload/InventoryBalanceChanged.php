<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Webhook\Payload;

use Swag\PayPal\IZettle\Api\Webhook\Payload\InventoryBalanceChanged\Balance;

class InventoryBalanceChanged extends AbstractPayload
{
    /**
     * @var Balance[]
     */
    protected $balanceBefore;

    /**
     * @var Balance[]
     */
    protected $balanceAfter;

    /**
     * @return Balance[]
     */
    public function getBalanceBefore(): array
    {
        return $this->balanceBefore;
    }

    /**
     * @return Balance[]
     */
    public function getBalanceAfter(): array
    {
        return $this->balanceAfter;
    }

    /**
     * @param Balance[] $balanceBefore
     */
    protected function setBalanceBefore(array $balanceBefore): void
    {
        $this->balanceBefore = $balanceBefore;
    }

    /**
     * @param Balance[] $balanceAfter
     */
    protected function setBalanceAfter(array $balanceAfter): void
    {
        $this->balanceAfter = $balanceAfter;
    }
}
