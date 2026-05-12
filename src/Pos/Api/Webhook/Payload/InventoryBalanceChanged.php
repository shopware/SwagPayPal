<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook\Payload;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Webhook\Payload\InventoryBalanceChanged\Balance;
use Swag\PayPal\Pos\Api\Webhook\Payload\InventoryBalanceChanged\Updated;

#[Package('checkout')]
class InventoryBalanceChanged extends AbstractPayload
{
    /**
     * @var Balance[]
     */
    protected array $balanceBefore;

    /**
     * @var Balance[]
     */
    protected array $balanceAfter;

    protected Updated $updated;

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

    public function getUpdated(): Updated
    {
        return $this->updated;
    }

    public function setUpdated(Updated $updated): void
    {
        $this->updated = $updated;
    }
}
