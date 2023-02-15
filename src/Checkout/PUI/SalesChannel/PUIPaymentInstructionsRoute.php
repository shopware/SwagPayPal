<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\SalesChannel;

use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\Exception\UnnecessaryTransitionException;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\PUI\Exception\MissingPaymentInstructionsException;
use Swag\PayPal\Checkout\PUI\Exception\PaymentInstructionsNotReadyException;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Compatibility\Exception;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class PUIPaymentInstructionsRoute extends AbstractPUIPaymentInstructionsRoute
{
    private EntityRepository $orderTransactionRepository;

    private OrderResource $orderResource;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private TransactionDataService $transactionDataService;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $orderTransactionRepository,
        OrderResource $orderResource,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        TransactionDataService $transactionDataService
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->orderResource = $orderResource;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->transactionDataService = $transactionDataService;
    }

    public function getDecorated(): AbstractPUIPaymentInstructionsRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Since("4.2.0")
     *
     * @OA\Get(
     *     path="/store-api/paypal/pui/payment-instructions/{transactionId}",
     *     description="Tries to get payment instructions for PUI payments",
     *     operationId="getPUIPaymentInstructions",
     *     tags={"Store API", "PayPal"},
     *
     *     @OA\Parameter(
     *         name="transactionId",
     *         description="Identifier of the order transaction to be fetched",
     *
     *         @OA\Schema(type="string", pattern="^[0-9a-f]{32}$"),
     *         in="path",
     *         required=true
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="The payment instructions of the order"
     *    )
     * )
     *
     * @Route(
     *     "/store-api/paypal/pui/payment-instructions/{transactionId}",
     *      name="store-api.paypal.pui.payment_instructions",
     *      methods={"GET"},
     *      defaults={"_loginRequired"=true, "_loginRequiredAllowGuest"=true}
     * )
     *
     * @throws ShopwareHttpException
     */
    public function getPaymentInstructions(string $transactionId, SalesChannelContext $salesChannelContext): PUIPaymentInstructionsResponse
    {
        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $this->orderTransactionRepository->search(new Criteria([$transactionId]), $salesChannelContext->getContext())->first();

        if ($transaction === null) {
            throw Exception::orderTransactionNotFound($transactionId);
        }

        $customFields = $transaction->getCustomFields() ?? [];
        if (isset($customFields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION])
            && \is_array($customFields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION])) {
            $instructions = new PayUponInvoice();
            $instructions->assign($customFields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION]);

            return new PUIPaymentInstructionsResponse($instructions);
        }

        $paypalOrderId = $customFields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID] ?? null;
        if (!$paypalOrderId) {
            throw Exception::orderTransactionNotFound($transactionId);
        }

        $order = $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannelId());

        try {
            if ($order->getStatus() === PaymentStatusV2::ORDER_APPROVED) {
                $this->orderTransactionStateHandler->authorize($transactionId, $salesChannelContext->getContext());
            }

            if ($order->getStatus() === PaymentStatusV2::ORDER_VOIDED) {
                $this->orderTransactionStateHandler->fail($transactionId, $salesChannelContext->getContext());
            }
        } catch (UnnecessaryTransitionException|IllegalTransitionException $e) {
            // do nothing here, it's ok, probably something got mixed up in the order of requests
        }

        if ($order->getStatus() !== PaymentStatusV2::ORDER_COMPLETED) {
            throw new PaymentInstructionsNotReadyException($transactionId);
        }

        $paymentSource = $order->getPaymentSource();
        if (!$paymentSource) {
            throw new MissingPaymentInstructionsException($transactionId);
        }

        $instructions = $paymentSource->getPayUponInvoice();
        if (!$instructions) {
            throw new MissingPaymentInstructionsException($transactionId);
        }

        $this->orderTransactionRepository->update([[
            'id' => $transactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION => $instructions,
            ],
        ]], $salesChannelContext->getContext());

        $this->transactionDataService->setResourceId($order, $transactionId, $salesChannelContext->getContext());

        $this->orderTransactionStateHandler->paid($transactionId, $salesChannelContext->getContext());

        return new PUIPaymentInstructionsResponse($instructions);
    }
}
