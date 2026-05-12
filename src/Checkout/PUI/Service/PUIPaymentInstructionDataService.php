<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\PUI\Exception\PaymentInstructionsNotReadyException;
use Swag\PayPal\Checkout\PUI\PUIPaymentInstructionData;
use Swag\PayPal\Checkout\PUI\SalesChannel\AbstractPUIPaymentInstructionsRoute;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class PUIPaymentInstructionDataService
{
    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    private AbstractPUIPaymentInstructionsRoute $puiPaymentInstructionsRoute;

    private RouterInterface $router;

    /**
     * @internal
     */
    public function __construct(
        PaymentMethodDataRegistry $paymentMethodDataRegistry,
        AbstractPUIPaymentInstructionsRoute $puiPaymentInstructionsRoute,
        RouterInterface $router,
    ) {
        $this->paymentMethodDataRegistry = $paymentMethodDataRegistry;
        $this->puiPaymentInstructionsRoute = $puiPaymentInstructionsRoute;
        $this->router = $router;
    }

    public function buildFinishData(OrderTransactionEntity $orderTransaction, SalesChannelContext $salesChannelContext): ?PUIPaymentInstructionData
    {
        $paymentMethodId = $this->paymentMethodDataRegistry->getEntityIdFromData(
            $this->paymentMethodDataRegistry->getPaymentMethod(PUIMethodData::class),
            $salesChannelContext->getContext()
        );

        if ($paymentMethodId !== $orderTransaction->getPaymentMethodId()) {
            return null;
        }

        $data = new PUIPaymentInstructionData();
        $data->assign([
            'pollingUrl' => $this->router->generate('frontend.paypal.pui.payment_instructions', ['transactionId' => $orderTransaction->getId()]),
            'finishUrl' => $this->router->generate('frontend.checkout.finish.page', ['orderId' => $orderTransaction->getOrderId()]),
            'errorUrl' => $this->router->generate('frontend.account.edit-order.page', [
                'orderId' => $orderTransaction->getOrderId(),
                'error-code' => 'CHECKOUT__PAYPAL_PAYMENT_DECLINED',
            ]),
            'paymentMethodId' => $paymentMethodId,
        ]);

        try {
            $data->setPaymentInstructions($this->puiPaymentInstructionsRoute->getPaymentInstructions($orderTransaction->getId(), $salesChannelContext)->getPaymentInstructions());
        } catch (PaymentInstructionsNotReadyException $e) {
            // do nothing, we will poll then
        }

        return $data;
    }
}
