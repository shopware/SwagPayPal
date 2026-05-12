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
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\PUI\Service\PUICustomerDataService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class PUIHandler extends AbstractPaymentMethodHandler
{
    public const PUI_FRAUD_NET_SESSION_ID = 'payPalPuiFraudnetSessionId';

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
        private readonly PUICustomerDataService $puiCustomerDataService,
    ) {
        parent::__construct($settingsValidationService, $stateMachineRegistry, $orderExecuteService, $orderPatchService, $transactionDataService, $orderResource, $vaultTokenService, $orderTransactionRepository, $orderBuilder);
    }

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct,
    ): ?RedirectResponse {
        $fraudnetSessionId = $request->request->get(self::PUI_FRAUD_NET_SESSION_ID);
        if (!$fraudnetSessionId) {
            throw PaymentException::syncProcessInterrupted($transaction->getOrderTransactionId(), 'Missing Fraudnet session id');
        }

        $dataBag = new RequestDataBag($request->request->all());
        $this->puiCustomerDataService->checkForCustomerData($transaction, $dataBag, $context);

        return parent::pay($request, $transaction, $context, $validateStruct);
    }

    protected function isVaultable(): bool
    {
        return false;
    }

    protected function requirePreparedOrder(): bool
    {
        return false;
    }

    protected function executeOrder(PaymentTransactionStruct $transaction, Order $paypalOrder, OrderEntity $order, OrderTransactionEntity $orderTransaction, Context $context, bool $isUserPresent = true): Order
    {
        // PUI orders are created with `ORDER_COMPLETE_ON_PAYMENT_APPROVAL`
        // Therefore it will automatically capture the payment
        return $paypalOrder;
    }

    protected function getProgressTransactionState(): string
    {
        return StateMachineTransitionActions::ACTION_PROCESS;
    }

    protected function getMetaDataId(Request $request): ?string
    {
        return $request->request->getString(self::PUI_FRAUD_NET_SESSION_ID);
    }
}
