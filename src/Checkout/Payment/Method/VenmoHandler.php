<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\RecurringPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\RecurringPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\VenmoOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;

#[Package('checkout')]
class VenmoHandler extends AbstractSyncAPMHandler implements RecurringPaymentHandlerInterface
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
        private readonly VenmoOrderBuilder $orderBuilder,
        private readonly OrderConverter $orderConverter,
    ) {
        parent::__construct($settingsValidationService, $orderTransactionStateHandler, $orderExecuteService, $orderPatchService, $transactionDataService, $logger, $orderResource, $vaultTokenService);
    }

    public function captureRecurring(RecurringPaymentTransactionStruct $transaction, Context $context): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $subscription = $this->vaultTokenService->getSubscription($transaction);
        if (!$subscription) {
            throw PaymentException::recurringInterrupted($transactionId, 'Subscription not found');
        }

        $salesChannelContext = $this->orderConverter->assembleSalesChannelContext($transaction->getOrder(), $context);

        try {
            $this->settingsValidationService->validate($salesChannelContext->getSalesChannelId());
            $paypalOrder = $this->orderBuilder->getOrder($transaction, $salesChannelContext, new RequestDataBag());
            $updateTime = $transaction->getOrderTransaction()->getUpdatedAt();
            $response = $this->orderResource->create(
                $paypalOrder,
                $salesChannelContext->getSalesChannelId(),
                PartnerAttributionId::PAYPAL_PPCP,
                true,
                $transactionId . ($updateTime ? $updateTime->getTimestamp() : ''),
            );

            $this->transactionDataService->setOrderId(
                $transactionId,
                $response->getId(),
                PartnerAttributionId::PAYPAL_PPCP,
                $salesChannelContext
            );
            $this->transactionDataService->setResourceId($response, $transactionId, $salesChannelContext->getContext());
            $this->orderExecuteService->captureOrAuthorizeOrder(
                $transactionId,
                $response,
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext(),
                PartnerAttributionId::PAYPAL_PPCP
            );
        } catch (PaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            throw PaymentException::recurringInterrupted($transactionId, $e->getMessage());
        }
    }
}
