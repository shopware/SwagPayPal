<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Patch;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @deprecated tag:v6.0.0 - will be removed, use PayPalHandler instead
 */
class EcsSpbHandler extends AbstractPaymentHandler
{
    private PurchaseUnitPatchBuilder $purchaseUnitPatchBuilder;

    private OrderResource $orderResource;

    private TransactionDataService $transactionDataService;

    private LoggerInterface $logger;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepo,
        PurchaseUnitPatchBuilder $purchaseUnitPatchBuilder,
        OrderResource $orderResource,
        TransactionDataService $transactionDataService,
        LoggerInterface $logger
    ) {
        parent::__construct($orderTransactionRepo);
        $this->purchaseUnitPatchBuilder = $purchaseUnitPatchBuilder;
        $this->orderResource = $orderResource;
        $this->transactionDataService = $transactionDataService;
        $this->logger = $logger;
    }

    public function handleEcsPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): RedirectResponse {
        $this->logger->debug('Started');
        $paypalOrderId = $dataBag->get(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        $orderTransaction = $transaction->getOrderTransaction();
        $orderTransactionId = $orderTransaction->getId();

        $this->transactionDataService->setOrderId(
            $orderTransactionId,
            $paypalOrderId,
            PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT,
            $salesChannelContext->getContext()
        );

        $order = $transaction->getOrder();

        $patches = [
            $this->purchaseUnitPatchBuilder->createFinalPurchaseUnitPatch(
                $order,
                $orderTransaction,
                $salesChannelContext,
            ),
        ];

        $this->patchPaypalOrder(
            $patches,
            $paypalOrderId,
            $salesChannelContext->getSalesChannelId(),
            $orderTransactionId,
            PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT
        );

        return $this->createResponse(
            $transaction->getReturnUrl(),
            $paypalOrderId,
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID
        );
    }

    public function handleSpbPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $this->logger->debug('Started');
        $paypalOrderId = $dataBag->get(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        $this->transactionDataService->setOrderId(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrderId,
            PartnerAttributionId::SMART_PAYMENT_BUTTONS,
            $salesChannelContext->getContext()
        );

        return $this->createResponse(
            $transaction->getReturnUrl(),
            $paypalOrderId,
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID
        );
    }

    private function createResponse(
        string $returnUrl,
        string $paypalOrderId,
        string $payPalType
    ): RedirectResponse {
        $parameters = \http_build_query([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => $paypalOrderId,
            $payPalType => true,
        ]);

        return new RedirectResponse(\sprintf('%s&%s', $returnUrl, $parameters));
    }

    /**
     * @param Patch[] $patches
     */
    private function patchPaypalOrder(
        array $patches,
        string $paypalOrderId,
        string $salesChannelId,
        string $orderTransactionId,
        string $partnerAttributionId
    ): void {
        try {
            $this->orderResource->update(
                $patches,
                $paypalOrderId,
                $salesChannelId,
                $partnerAttributionId
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $orderTransactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', \PHP_EOL, $e->getMessage())
            );
        }
    }
}
