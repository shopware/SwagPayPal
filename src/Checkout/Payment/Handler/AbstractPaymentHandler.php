<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Handler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\SwagPayPal;

abstract class AbstractPaymentHandler
{
    public const PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME = 'paypalOrderId';

    /**
     * @var EntityRepositoryInterface
     */
    protected $orderTransactionRepo;

    public function __construct(EntityRepositoryInterface $orderTransactionRepo)
    {
        $this->orderTransactionRepo = $orderTransactionRepo;
    }

    protected function addPayPalOrderId(
        string $orderTransactionId,
        string $paypalOrderId,
        string $partnerAttributionId,
        Context $context
    ): void {
        $data = [
            'id' => $orderTransactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => $paypalOrderId,
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID => $partnerAttributionId,
            ],
        ];
        $this->orderTransactionRepo->update([$data], $context);
    }
}
