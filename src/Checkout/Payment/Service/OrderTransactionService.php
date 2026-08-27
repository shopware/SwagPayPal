<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\CheckoutException;

#[Package('checkout')]
class OrderTransactionService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function reserve(string $paypalOrderId, string $transactionId): void
    {
        try {
            $this->connection->insert('swag_paypal_order_transactions', [
                'order_transaction_id' => Uuid::fromHexToBytes($transactionId),
                'order_transaction_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'paypal_order_id' => $paypalOrderId,
                'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        } catch (UniqueConstraintViolationException) {
            $differentTransactionExists = $this->connection->fetchOne(
                'SELECT 1 FROM `swag_paypal_order_transactions`
                 WHERE `paypal_order_id` = :paypalOrderId
                   AND `order_transaction_id` != :transactionId',
                [
                    'paypalOrderId' => $paypalOrderId,
                    'transactionId' => Uuid::fromHexToBytes($transactionId),
                ],
            );

            if ($differentTransactionExists !== false) {
                throw CheckoutException::paypalOrderAlreadyUsed();
            }
        }
    }
}
