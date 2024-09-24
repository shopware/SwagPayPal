<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\AbstractPaymentTransactionStructFactory;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ApplePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\GooglePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\VenmoOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class CreateOrderRoute extends AbstractCreateOrderRoute
{
    public const FAKE_URL = 'https://www.example.com/';

    /**
     * @internal
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly EntityRepository $orderRepository,
        private readonly PayPalOrderBuilder $payPalOrderBuilder,
        private readonly ACDCOrderBuilder $acdcOrderBuilder,
        private readonly ApplePayOrderBuilder $applePayOrderBuilder,
        private readonly GooglePayOrderBuilder $googlePayOrderBuilder,
        private readonly VenmoOrderBuilder $venmoOrderBuilder,
        private readonly OrderResource $orderResource,
        private readonly LoggerInterface $logger,
        private readonly AbstractPaymentTransactionStructFactory $paymentTransactionStructFactory,
    ) {
    }

    public function getDecorated(): AbstractCreateOrderRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @throws CustomerNotLoggedInException
     */
    #[OA\Post(
        path: '/store-api/paypal/create-order',
        operationId: 'createPayPalOrder',
        description: 'Creates a PayPal order from the existing cart or an order',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(
                property: 'product',
                description: 'Use an existing order id to create PayPal order',
                type: 'string',
                default: 'ppcp',
            ),
            new OA\Property(
                property: 'orderId',
                description: 'Use an existing order id to create PayPal order',
                type: 'string',
            ),
        ])),
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: 200,
            description: 'Returns the created PayPal order id',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'token',
                type: 'string',
            )])
        )]
    )]
    #[Route(path: '/store-api/paypal/create-order', name: 'store-api.paypal.create_order', methods: ['POST'])]
    #[Route(path: '/store-api/subscription/paypal/create-order', name: 'store-api.subscription.paypal.create_order', defaults: ['_subscriptionCart' => true, '_subscriptionContext' => true], methods: ['POST'])]
    public function createPayPalOrder(SalesChannelContext $salesChannelContext, Request $request): TokenResponse
    {
        try {
            $requestDataBag = new RequestDataBag($request->request->all());
            $this->logger->debug('Started', ['request' => $requestDataBag->all()]);
            $customer = $salesChannelContext->getCustomer();
            if ($customer === null) {
                throw CartException::customerNotLoggedIn();
            }

            $orderId = $requestDataBag->getAlnum('orderId');

            $orderBuilder = match ($requestDataBag->get('product')) {
                'acdc' => $this->acdcOrderBuilder,
                'applepay' => $this->applePayOrderBuilder,
                'googlepay' => $this->googlePayOrderBuilder,
                'venmo' => $this->venmoOrderBuilder,
                default => $this->payPalOrderBuilder,
            };

            $paypalOrder = $orderId
                ? $this->getOrderFromOrder($orderBuilder, $orderId, $customer, $requestDataBag, $salesChannelContext)
                : $this->getOrderFromCart($orderBuilder, $salesChannelContext, $requestDataBag);

            $salesChannelId = $salesChannelContext->getSalesChannelId();
            $response = $this->orderResource->create($paypalOrder, $salesChannelId, $this->getPartnerAttributionId($requestDataBag));

            return new TokenResponse($response->getId());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['error' => $e]);

            throw $e;
        }
    }

    private function getOrderFromCart(
        AbstractOrderBuilder $orderBuilder,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
    ): Order {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        return $orderBuilder->getOrderFromCart($cart, $salesChannelContext, $requestDataBag);
    }

    private function getOrderFromOrder(
        AbstractOrderBuilder $orderBuilder,
        string $orderId,
        CustomerEntity $customer,
        RequestDataBag $requestDataBag,
        SalesChannelContext $salesChannelContext,
    ): Order {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('billingAddress.country');
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('subscription');
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();

        if ($order === null) {
            throw OrderException::orderNotFound($orderId);
        }

        $orderCustomer = $order->getOrderCustomer();
        if ($orderCustomer !== null && $orderCustomer->getCustomerId() !== null && $orderCustomer->getCustomerId() !== $customer->getId()) {
            throw OrderException::orderNotFound($orderId);
        }

        $transactionCollection = $order->getTransactions();
        if ($transactionCollection === null) {
            throw PaymentException::invalidOrder($orderId);
        }

        $transaction = $transactionCollection->last();
        if ($transaction === null) {
            throw PaymentException::invalidOrder($orderId);
        }

        return $orderBuilder->getOrder(
            $this->paymentTransactionStructFactory->sync($transaction, $order),
            $salesChannelContext,
            $requestDataBag,
        );
    }

    private function getPartnerAttributionId(RequestDataBag $requestDataBag): string
    {
        $product = $requestDataBag->get('product');

        if (!\is_string($product) || $product === '') {
            return PartnerAttributionId::PAYPAL_PPCP;
        }

        if (!isset(PartnerAttributionId::PRODUCT_ATTRIBUTION[$product])) {
            return PartnerAttributionId::PAYPAL_PPCP;
        }

        return PartnerAttributionId::PRODUCT_ATTRIBUTION[$product];
    }
}
