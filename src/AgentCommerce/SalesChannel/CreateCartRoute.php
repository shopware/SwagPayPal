<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Coupon;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Swag\PayPal\AgentCommerce\Util\PayPalCartFactory;
use Swag\PayPal\AgentCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\AgentCommerce\Util\ShopwareCartTransformer;
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
        private readonly RegisterRoute $registerRoute,
        private readonly ProductLineItemFactory $lineItemFactory
    ) {
    }

    #[Route('/api/paypal/v1/merchant-cart', name: 'api.paypal.merchant-cart', methods: [Request::METHOD_POST])]
    public function createCart(Request $request, SalesChannelContext $salesChannelContext): AgentCartResponse
    {
        $payPalCart = (new PayPalCartFactory())->create($request->getPayload()->all());
        if ($payPalCart->getCustomer() && !$salesChannelContext->getCustomer()) {
            $salesChannelContext = $this->registerAndLoginCustomer($payPalCart, $salesChannelContext);
        }

        $lineItems = [];
        foreach ($payPalCart->getItems() as $item) {
            $lineItems[] = $this->lineItemFactory->create(['id' => $item->getVariantId(), 'quantity' => $item->getQuantity()], $salesChannelContext);
        }

        if ($payPalCart->isset('coupons')) {
            $lineItems = array_merge($lineItems, $this->handleCoupons($payPalCart, $salesChannelContext));
        }

        $swCart = $this->cartService->createNew($salesChannelContext->getToken());
        $swCart = $this->cartService->add($swCart, $lineItems, $salesChannelContext);

        $createdPayPalCart = $this->payPalCartTransformer->convertToPayPalCart($swCart, $salesChannelContext, $payPalCart);
        $createdPayPalCart->setStatus($createdPayPalCart->getValidationStatus() === PayPalCart::VALIDATION_STATUS__VALID ? PayPalCart::STATUS__CREATED : PayPalCart::STATUS__INCOMPLETE);

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

    /**
     * @return LineItem[]
     */
    private function handleCoupons(PayPalCart $payPalCart, SalesChannelContext $salesChannelContext): array
    {
        $items = [];
        foreach ($payPalCart->getCoupons() as $item) {
            if ($item->getAction() === Coupon::APPLY) {
                // TODO: coming soon
            }
        }

        return $items;
    }
}
