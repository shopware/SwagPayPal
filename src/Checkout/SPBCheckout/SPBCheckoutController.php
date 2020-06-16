<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\Builder\CartPaymentBuilderInterface;
use Swag\PayPal\Payment\Builder\OrderPaymentBuilderInterface;
use Swag\PayPal\Payment\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Util\PaymentTokenExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SPBCheckoutController extends AbstractController
{
    private const FAKE_URL = 'https://www.example.com/';

    /**
     * @var CartPaymentBuilderInterface
     */
    private $cartPaymentBuilder;

    /**
     * @var OrderPaymentBuilderInterface
     */
    private $orderPaymentBuilder;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var PayerInfoPatchBuilder
     */
    private $payerInfoPatchBuilder;

    /**
     * @var ShippingAddressPatchBuilder
     */
    private $shippingAddressPatchBuilder;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        CartPaymentBuilderInterface $cartPaymentBuilder,
        OrderPaymentBuilderInterface $orderPaymentBuilder,
        CartService $cartService,
        PaymentResource $paymentResource,
        PayerInfoPatchBuilder $payerInfoPatchBuilder,
        ShippingAddressPatchBuilder $shippingAddressPatchBuilder,
        EntityRepositoryInterface $orderRepository
    ) {
        $this->cartPaymentBuilder = $cartPaymentBuilder;
        $this->orderPaymentBuilder = $orderPaymentBuilder;
        $this->cartService = $cartService;
        $this->paymentResource = $paymentResource;
        $this->payerInfoPatchBuilder = $payerInfoPatchBuilder;
        $this->shippingAddressPatchBuilder = $shippingAddressPatchBuilder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @RouteScope(scopes={"sales-channel-api"})
     * @Route("/sales-channel-api/v{version}/_action/paypal/spb/create-payment", name="sales-channel-api.action.paypal.spb.create_payment", methods={"POST"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function createPayment(SalesChannelContext $salesChannelContext, Request $request): JsonResponse
    {
        $customer = $salesChannelContext->getCustomer();
        if ($customer === null) {
            throw new CustomerNotLoggedInException();
        }

        $orderId = $request->request->get('orderId');
        if ($orderId === null) {
            $payment = $this->getPaymentFromCart($salesChannelContext);
        } else {
            $payment = $this->getPaymentFromOrder($orderId, $salesChannelContext);
        }
        $payment->getApplicationContext()->setUserAction(ApplicationContext::USER_ACTION_TYPE_CONTINUE);

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $response = $this->paymentResource->create(
            $payment,
            $salesChannelId,
            PartnerAttributionId::SMART_PAYMENT_BUTTONS
        );

        $patches = [
            $this->shippingAddressPatchBuilder->createShippingAddressPatch($customer),
            $this->payerInfoPatchBuilder->createPayerInfoPatch($customer),
        ];
        $this->paymentResource->patch($patches, $response->getId(), $salesChannelId);

        return new JsonResponse([
            'token' => PaymentTokenExtractor::extract($response),
        ]);
    }

    private function getPaymentFromCart(SalesChannelContext $salesChannelContext): Payment
    {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        return $this->cartPaymentBuilder->getPayment(
            $cart,
            $salesChannelContext,
            self::FAKE_URL,
            false
        );
    }

    private function getPaymentFromOrder(string $orderId, SalesChannelContext $salesChannelContext): Payment
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('lineItems');
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        $transactionCollection = $order->getTransactions();
        if ($transactionCollection === null) {
            throw new InvalidOrderException($orderId);
        }

        $transaction = $transactionCollection->first();
        if ($transaction === null) {
            throw new InvalidOrderException($orderId);
        }

        $paymentTransaction = new AsyncPaymentTransactionStruct($transaction, $order, self::FAKE_URL);

        return $this->orderPaymentBuilder->getPayment($paymentTransaction, $salesChannelContext);
    }
}
