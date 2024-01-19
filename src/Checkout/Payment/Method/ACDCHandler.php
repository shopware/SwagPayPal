<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ACDC\ACDCValidatorInterface;
use Swag\PayPal\Checkout\ACDC\Exception\ACDCValidationFailedException;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;

#[Package('checkout')]
class ACDCHandler extends AbstractSyncAPMHandler
{
    private ACDCValidatorInterface $acdcValidator;

    /**
     * @internal
     */
    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        OrderExecuteService $orderExecuteService,
        OrderPatchService $orderPatchService,
        TransactionDataService $transactionDataService,
        LoggerInterface $logger,
        OrderResource $orderResource,
        ACDCValidatorInterface $acdcValidator
    ) {
        parent::__construct(
            $settingsValidationService,
            $orderTransactionStateHandler,
            $orderExecuteService,
            $orderPatchService,
            $transactionDataService,
            $logger,
            $orderResource
        );

        $this->acdcValidator = $acdcValidator;
    }

    protected function executeOrder(SyncPaymentTransactionStruct $transaction, Order $paypalOrder, SalesChannelContext $salesChannelContext): Order
    {
        // fallback button
        $paymentSource = $paypalOrder->getPaymentSource();
        if ($paymentSource === null) {
            throw new MissingPayloadException($paypalOrder->getId(), 'paymentSource');
        }

        if ($paymentSource->getPaypal() !== null && $paymentSource->getCard() === null) {
            return parent::executeOrder($transaction, $paypalOrder, $salesChannelContext);
        }

        if (!$this->acdcValidator->validate($paypalOrder, $transaction, $salesChannelContext)) {
            throw ACDCValidationFailedException::syncACDCValidationFailed($transaction->getOrderTransaction()->getId());
        }

        return parent::executeOrder($transaction, $paypalOrder, $salesChannelContext);
    }
}
