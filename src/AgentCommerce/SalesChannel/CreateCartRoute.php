<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Shopware\PayPalSDK\Struct\V2\Patch;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Swag\PayPal\AgentCommerce\Util\PayPalCartFactory;
use Swag\PayPal\AgentCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\AgentCommerce\Util\ShopwareCartTransformer;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['paypal-agent'], '_agentScope' => [AgentSource::SCOPE_CART]])]
class CreateCartRoute extends AbstractAgentCommerceRoute
{
    public function __construct(
        protected SalesChannelContextService $contextService,
        private readonly CartService $cartService,
        private readonly PayPalCartTransformer $payPalCartTransformer,
        private readonly ShopwareCartTransformer $shopwareCartTransformer,
        private readonly AbstractRegisterRoute $registerRoute,
        private readonly AbstractOrderBuilder $orderBuilder,
        private readonly OrderResource $orderResource,
    ) {
    }

    #[Route('/api/paypal/v1/merchant-cart', name: 'api.paypal.merchant-cart', methods: [Request::METHOD_POST])]
    public function createCart(Request $request, SalesChannelContext $salesChannelContext): AgentCartResponse
    {
        $payPalCart = (new PayPalCartFactory())->create($request->getPayload()->all());
        if ($payPalCart->getCustomer() && !$salesChannelContext->getCustomer()) {
            $salesChannelContext = $this->registerAndLoginCustomer($payPalCart, $salesChannelContext);
        }

        $swCart = $this->cartService->createNew($salesChannelContext->getToken());
        $swCart = $this->cartService->add($swCart, $this->shopwareCartTransformer->getLineItems($payPalCart, $salesChannelContext), $salesChannelContext);

        $orderId = $this->upsertPayPalOrder($payPalCart, $swCart, $request->request->all(), $salesChannelContext);

        $createdPayPalCart = $this->payPalCartTransformer->convertToPayPalCart($swCart, $salesChannelContext, $payPalCart);

        $createdPayPalCart->setStatus($createdPayPalCart->getValidationStatus() === PayPalCart::VALIDATION_STATUS__VALID ? PayPalCart::STATUS__CREATED : PayPalCart::STATUS__INCOMPLETE);
        $createdPayPalCart->setPaymentMethod($this->createPaymentMethod($orderId));

        $response = new AgentCartResponse($createdPayPalCart);
        if ($createdPayPalCart->getStatus() === PayPalCart::STATUS__CREATED) {
            $response->setStatusCode(Response::HTTP_CREATED);
        }

        return $response;
    }

    private function registerAndLoginCustomer(PayPalCart $payPalCart, SalesChannelContext $salesChannelContext): SalesChannelContext
    {
        $customerData = $this->shopwareCartTransformer->extractCustomerData($payPalCart, $salesChannelContext->getSalesChannelId(), $salesChannelContext->getContext());

        $this->registerRoute->register(new RequestDataBag($customerData), $salesChannelContext, false);

        return $this->createSalesChannelContext($salesChannelContext->getToken(), $salesChannelContext->getSalesChannelId(), $salesChannelContext->getContext());
    }

    private function upsertPayPalOrder(PayPalCart $payPalCart, Cart $swCart, array $requestData, SalesChannelContext $salesChannelContext): string
    {
        $order = $this->orderBuilder->getOrderFromCart($swCart, $salesChannelContext, new RequestDataBag($requestData));
        $orderId = $payPalCart->getPaymentMethod()?->getToken();

        if ($orderId) {
            $purchaseUnit = $order->getPurchaseUnits()->first();
            $purchaseUnitArray = \json_decode((string) \json_encode($purchaseUnit), true);

            $purchaseUnitPatch = new Patch();
            $purchaseUnitPatch->setOp(Patch::OPERATION_REPLACE);
            $purchaseUnitPatch->setPath('/purchase_units/@reference_id==\'default\'');
            $purchaseUnitPatch->setValue($purchaseUnitArray);

            $this->orderResource->update([$purchaseUnitPatch], $orderId, $salesChannelContext->getSalesChannelId(), PartnerAttributionId::PAYPAL_PPCP);
        } else {
            $orderId = $this->orderResource->create($order, $salesChannelContext->getSalesChannelId(), PartnerAttributionId::PAYPAL_PPCP)->getId();
        }

        return $orderId;
    }
}
