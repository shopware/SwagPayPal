<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\Exception\UnnecessaryTransitionException;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\PUI\Exception\MissingPaymentInstructionsException;
use Swag\PayPal\Checkout\PUI\Exception\PaymentInstructionsNotReadyException;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class PUIInstructionsFetchService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $orderTransactionRepository,
        private readonly OrderResource $orderResource,
        private readonly OrderTransactionStateHandler $orderTransactionStateHandler,
        private readonly TransactionDataService $transactionDataService,
    ) {
    }

    /**
     * @throws PaymentInstructionsNotReadyException
     * @throws MissingPaymentInstructionsException
     * @throws OrderException
     */
    public function fetchPUIInstructions(OrderTransactionEntity $transaction, string $salesChannelId, Context $context): PayUponInvoice
    {
        $puiInstructions = $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION);
        if (\is_array($puiInstructions)) {
            return (new PayUponInvoice())->assign($puiInstructions);
        }

        $paypalOrderId = $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID);
        if (!$paypalOrderId) {
            throw OrderException::orderTransactionNotFound($transaction->getId());
        }

        $order = $this->orderResource->get($paypalOrderId, $salesChannelId);
        try {
            if ($order->getStatus() === PaymentStatusV2::ORDER_APPROVED) {
                $this->orderTransactionStateHandler->authorize($transaction->getId(), $context);
            }

            if ($order->getStatus() === PaymentStatusV2::ORDER_VOIDED) {
                $this->orderTransactionStateHandler->fail($transaction->getId(), $context);
            }
        } catch (UnnecessaryTransitionException|IllegalTransitionException $e) {
            // do nothing here, it's ok, probably something got mixed up in the order of requests
        }

        if ($order->getStatus() !== PaymentStatusV2::ORDER_COMPLETED) {
            throw new PaymentInstructionsNotReadyException($transaction->getId());
        }

        $instructions = $order->getPaymentSource()?->getPayUponInvoice();
        if (!$instructions) {
            throw new MissingPaymentInstructionsException($transaction->getId());
        }

        $this->orderTransactionRepository->update([[
            'id' => $transaction->getId(),
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION => $instructions,
            ],
        ]], $context);

        $this->transactionDataService->setResourceId($order, $transaction->getId(), $context);

        if ($transaction->getStateMachineState()?->getTechnicalName() !== OrderTransactionStates::STATE_PAID) {
            $this->orderTransactionStateHandler->paid($transaction->getId(), $context);
        }

        return $instructions;
    }
}
