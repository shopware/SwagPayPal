<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Card\CardValidatorInterface;
use Swag\PayPal\Checkout\Card\Exception\CardValidationFailedException;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;

#[Package('checkout')]
class GooglePayHandler extends AbstractPaymentMethodHandler
{
    /**
     * @internal
     */
    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        StateMachineRegistry $stateMachineRegistry,
        OrderExecuteService $orderExecuteService,
        OrderPatchService $orderPatchService,
        TransactionDataService $transactionDataService,
        OrderResource $orderResource,
        VaultTokenService $vaultTokenService,
        EntityRepository $orderTransactionRepository,
        AbstractOrderBuilder $orderBuilder,
        private readonly CardValidatorInterface $googlePayValidator,
    ) {
        parent::__construct($settingsValidationService, $stateMachineRegistry, $orderExecuteService, $orderPatchService, $transactionDataService, $orderResource, $vaultTokenService, $orderTransactionRepository, $orderBuilder);
    }

    protected function executeOrder(PaymentTransactionStruct $transaction, Order $paypalOrder, OrderEntity $order, OrderTransactionEntity $orderTransaction, Context $context, bool $isUserPresent = true): Order
    {
        if (!$this->googlePayValidator->validate($paypalOrder, $orderTransaction, $context)) {
            throw CardValidationFailedException::cardValidationFailed($transaction->getOrderTransactionId());
        }

        return parent::executeOrder($transaction, $paypalOrder, $order, $orderTransaction, $context, $isUserPresent);
    }

    protected function isVaultable(): bool
    {
        return false;
    }

    protected function requirePreparedOrder(): bool
    {
        return true;
    }
}
