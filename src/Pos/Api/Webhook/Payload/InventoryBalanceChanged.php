<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook\Payload;

use Swag\PayPal\Pos\Api\Webhook\Payload\InventoryBalanceChanged\Balance;

class InventoryBalanceChanged extends AbstractPayload
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Balance[]
     */
    protected $balanceBefore;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
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
     * @param Balance[] $balanceBefore
     */
    public function setBalanceBefore(array $balanceBefore): void
    {
        $this->balanceBefore = $balanceBefore;
    }

    /**
     * @return Balance[]
     */
    public function getBalanceAfter(): array
    {
        return $this->balanceAfter;
    }

    /**
     * @param Balance[] $balanceAfter
     */
    public function setBalanceAfter(array $balanceAfter): void
    {
        $this->balanceAfter = $balanceAfter;
    }
}
