<?php declare(strict_types=1);
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
use Swag\PayPal\Checkout\Card\CardValidatorInterface;
use Swag\PayPal\Checkout\Card\Exception\CardValidationFailedException;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;

#[Package('checkout')]
class GooglePayHandler extends AbstractSyncAPMHandler
{
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
        VaultTokenService $vaultTokenService,
        private readonly CardValidatorInterface $googlePayValidator,
    ) {
        parent::__construct($settingsValidationService, $orderTransactionStateHandler, $orderExecuteService, $orderPatchService, $transactionDataService, $logger, $orderResource, $vaultTokenService);
    }

    protected function executeOrder(SyncPaymentTransactionStruct $transaction, Order $paypalOrder, SalesChannelContext $salesChannelContext): Order
    {
        if (!$this->googlePayValidator->validate($paypalOrder, $transaction, $salesChannelContext)) {
            throw CardValidationFailedException::cardValidationFailed($transaction->getOrderTransaction()->getId());
        }

        return parent::executeOrder($transaction, $paypalOrder, $salesChannelContext);
    }
}
