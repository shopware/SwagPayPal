<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\PUI\Service\PUICustomerDataService;
use Swag\PayPal\OrdersApi\Builder\PUIOrderBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Package('checkout')]
class PUIHandler extends AbstractPaymentMethodHandler implements SynchronousPaymentHandlerInterface
{
    public const PUI_FRAUD_NET_SESSION_ID = 'payPalPuiFraudnetSessionId';

    /**
     * @deprecated tag:v10.0.0 - Will be removed, also delete corresponding snippets
     */
    private const ERROR_KEYS = [
        'PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED' => 'unverifiedInfo',
        'PAYMENT_SOURCE_DECLINED_BY_PROCESSOR' => 'declined',
    ];

    private PUIOrderBuilder $puiOrderBuilder;

    private OrderResource $orderResource;

    private TransactionDataService $transactionDataService;

    private PUICustomerDataService $puiCustomerDataService;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private SettingsValidationServiceInterface $settingsValidationService;

    private RequestStack $requestStack;

    private TranslatorInterface $translator;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        SettingsValidationServiceInterface $settingsValidationService,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        PUIOrderBuilder $puiOrderBuilder,
        OrderResource $orderResource,
        TransactionDataService $transactionDataService,
        PUICustomerDataService $puiCustomerDataService,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        LoggerInterface $logger,
    ) {
        $this->settingsValidationService = $settingsValidationService;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderResource = $orderResource;
        $this->puiOrderBuilder = $puiOrderBuilder;
        $this->transactionDataService = $transactionDataService;
        $this->puiCustomerDataService = $puiCustomerDataService;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $fraudnetSessionId = $dataBag->get(self::PUI_FRAUD_NET_SESSION_ID);

        if (!$fraudnetSessionId) {
            throw PaymentException::syncProcessInterrupted($transactionId, 'Missing Fraudnet session id');
        }

        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw CartException::customerNotLoggedIn();
        }

        try {
            $this->puiCustomerDataService->checkForCustomerData($transaction->getOrder(), $dataBag, $salesChannelContext);
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
            $this->orderTransactionStateHandler->process($transactionId, $salesChannelContext->getContext());

            $order = $this->puiOrderBuilder->getOrder(
                $transaction,
                $salesChannelContext,
                $dataBag,
            );

            $updateTime = $transaction->getOrderTransaction()->getUpdatedAt();

            $paypalOrderResponse = $this->orderResource->create(
                $order,
                $salesChannelContext->getSalesChannelId(),
                PartnerAttributionId::PAYPAL_PPCP,
                true,
                $transactionId . ($updateTime ? $updateTime->getTimestamp() : ''),
                $fraudnetSessionId
            );

            $this->transactionDataService->setOrderId(
                $transactionId,
                $paypalOrderResponse->getId(),
                PartnerAttributionId::PAYPAL_PPCP,
                $salesChannelContext
            );
        } catch (PaymentException $e) {
            if ($e->getParameter('orderTransactionId') === null && method_exists($e, 'setOrderTransactionId')) {
                $e->setOrderTransactionId($transactionId);
            }
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw PaymentException::syncProcessInterrupted($transactionId, $e->getMessage());
        }
    }

    /**
     * @deprecated tag:v10.0.0 - Will be removed. Use PayPalController::handleError instead
     */
    public function handleError(PayPalApiException $exception): void
    {
        if ($exception->getStatusCode() !== Response::HTTP_UNPROCESSABLE_ENTITY) {
            return;
        }

        $issue = $exception->getIssue();
        if (!$issue) {
            return;
        }

        if (!\array_key_exists($issue, self::ERROR_KEYS)) {
            return;
        }

        try {
            $session = $this->requestStack->getSession();
            if (!\method_exists($session, 'getFlashBag')) {
                throw new SessionNotFoundException();
            }

            $session->getFlashBag()->add(
                StorefrontController::DANGER,
                $this->translator->trans(\sprintf('paypal.payUponInvoice.error.%s', self::ERROR_KEYS[$issue]))
            );
        } catch (SessionNotFoundException $e) {
        }
    }
}
