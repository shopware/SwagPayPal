<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\OrdersApi\Builder\OrderFromCartBuilder;
use Swag\PayPal\OrdersApi\Builder\OrderFromOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"store-api"})
 */
class CreateOrderRoute extends AbstractCreateOrderRoute
{
    private const FAKE_URL = 'https://www.example.com/';

    private OrderFromCartBuilder $orderFromCartBuilder;

    private OrderFromOrderBuilder $orderFromOrderBuilder;

    private CartService $cartService;

    private EntityRepositoryInterface $orderRepository;

    private OrderResource $orderResource;

    private LoggerInterface $logger;

    public function __construct(
        CartService $cartService,
        EntityRepositoryInterface $orderRepository,
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
     * @Since("REPLACE_GLOBALLY_WITH_NEXT_VERSION")
     * @OA\Post(
     *     path="/store-api/paypal/create-order",
     *     description="Creates a PayPal order from the existing cart or an order",
     *     operationId="createPayPalOrder",
     *     tags={"Store API", "PayPal"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
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
     *     @OA\Response(
     *         response="200",
     *         description="The new token of the order"
     *    )
     * )
     *
     * @Route(
     *     "/store-api/paypal/create-order",
     *      name="store-api.paypal.create_order",
     *      methods={"POST"}
     * )
     *
     * @throws CustomerNotLoggedInException
     */
    public function createPayPalOrder(SalesChannelContext $salesChannelContext, Request $request): TokenResponse
    {
        try {
            $this->logger->debug('Started', ['request' => $request->request->all()]);
            $customer = $salesChannelContext->getCustomer();
            if ($customer === null) {
                throw new CustomerNotLoggedInException();
            }

            $orderId = $request->request->get('orderId');
            if (\is_string($orderId)) {
                $paypalOrder = $this->getOrderFromOrder($orderId, $salesChannelContext, $customer);
            } else {
                $paypalOrder = $this->getOrderFromCart($salesChannelContext, $customer);
            }

            $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
            $response = $this->orderResource->create($paypalOrder, $salesChannelId, $this->getPartnerAttributionId($request));

            return new TokenResponse($response->getId());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['error' => $e]);

            throw $e;
        }
    }

    private function getOrderFromCart(SalesChannelContext $salesChannelContext, CustomerEntity $customer): Order
    {
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        return $this->orderFromCartBuilder->getOrder($cart, $salesChannelContext, $customer);
    }

    private function getOrderFromOrder(
        string $orderId,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): Order {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('lineItems');
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        $transactionCollection = $order->getTransactions();
        if ($transactionCollection === null) {
            throw new InvalidOrderException($orderId);
        }

        $transaction = $transactionCollection->last();
        if ($transaction === null) {
            throw new InvalidOrderException($orderId);
        }

        return $this->orderFromOrderBuilder->getOrder(
            new AsyncPaymentTransactionStruct($transaction, $order, self::FAKE_URL),
            $salesChannelContext,
            $customer
        );
    }

    private function getPartnerAttributionId(Request $request): string
    {
        $product = $request->request->get('product');

        if (!\is_string($product) || $product === '') {
            return PartnerAttributionId::PAYPAL_PPCP;
        }

        if (!isset(PartnerAttributionId::PRODUCT_ATTRIBUTION[$product])) {
            return PartnerAttributionId::PAYPAL_PPCP;
        }

        return PartnerAttributionId::PRODUCT_ATTRIBUTION[$product];
    }
}
