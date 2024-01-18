<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\OrdersApi\Builder\OrderFromCartBuilder;
use Swag\PayPal\OrdersApi\Builder\OrderFromOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class CreateOrderRoute extends AbstractCreateOrderRoute
{
    public const FAKE_URL = 'https://www.example.com/';

    private OrderFromCartBuilder $orderFromCartBuilder;

    private OrderFromOrderBuilder $orderFromOrderBuilder;

    private CartService $cartService;

    private EntityRepository $orderRepository;

    private OrderResource $orderResource;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        CartService $cartService,
        EntityRepository $orderRepository,
        OrderFromOrderBuilder $orderFromOrderBuilder,
        OrderFromCartBuilder $orderFromCartBuilder,
        OrderResource $orderResource,
        LoggerInterface $logger
    ) {
        $this->cartService = $cartService;
        $this->orderRepository = $orderRepository;
        $this->orderFromOrderBuilder = $orderFromOrderBuilder;
        $this->orderFromCartBuilder = $orderFromCartBuilder;
        $this->orderResource = $orderResource;
        $this->logger = $logger;
    }

    public function getDecorated(): AbstractCreateOrderRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @OA\Post(
     *     path="/store-api/paypal/create-order",
     *     description="Creates a PayPal order from the existing cart or an order",
     *     operationId="createPayPalOrder",
     *     tags={"Store API", "PayPal"},
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="product",
     *                 type="string",
     *                 default="ppcp",
     *                 required=false,
     *                 description="Use an existing order id to create PayPal order",
     *             ),
     *             @OA\Property(
     *                 property="orderId",
     *                 type="string",
     *                 required=false,
     *                 description="Use an existing order id to create PayPal order",
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response="200",
     *         description="The new token of the order"
     *    )
     * )
     *
     * @throws CustomerNotLoggedInException
     */
    #[Route(path: '/store-api/paypal/create-order', name: 'store-api.paypal.create_order', methods: ['POST'])]
    #[Route(path: '/store-api/subscription/paypal/create-order', name: 'store-api.subscription.paypal.create_order', defaults: ['_isSubscriptionCart' => true, '_isSubscriptionContext' => true], methods: ['POST'])]
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
            $paypalOrder = $orderId
                ? $this->getOrderFromOrder($orderId, $customer, $requestDataBag, $salesChannelContext)
                : $this->getOrderFromCart($salesChannelContext, $request, $customer);

            if ($requestDataBag->get('product') === 'acdc') {
                $paypalOrder->setPaymentSource(null);
            }

            $salesChannelId = $salesChannelContext->getSalesChannelId();
            $response = $this->orderResource->create($paypalOrder, $salesChannelId, $this->getPartnerAttributionId($requestDataBag));

            return new TokenResponse($response->getId());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['error' => $e]);

            throw $e;
        }
    }

    private function getOrderFromCart(SalesChannelContext $salesChannelContext, Request $request, CustomerEntity $customer): Order
    {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        return $this->orderFromCartBuilder->getOrder($cart, $request, $salesChannelContext, $customer);
    }

    private function getOrderFromOrder(
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

        return $this->orderFromOrderBuilder->getOrder(
            new AsyncPaymentTransactionStruct($transaction, $order, self::FAKE_URL),
            $requestDataBag,
            $salesChannelContext,
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
