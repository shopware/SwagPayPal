<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\MessageQueue;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;
use Shopware\Core\Framework\Struct\JsonSerializableTrait;

#[Package('checkout')]
class TransactionStatusSyncMessage implements AsyncMessageInterface, \JsonSerializable
{
    use JsonSerializableTrait;

    public function __construct(
        protected string $transactionId,
        protected string $salesChannelId,
        protected ?string $payPalOrderId,
    ) {
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getPayPalOrderId(): ?string
    {
        return $this->payPalOrderId;
    }
}
