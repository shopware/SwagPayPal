<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Swag\PayPal\AgentCommerce\Util\ShopwareCartTransformer;
use Swag\PayPal\AgentCommerce\Validation\CartTokenValidator;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['paypal-agent'], '_agentScope' => [AgentSource::SCOPE_CHECKOUT]])]
class UpdateCartRoute extends AbstractAgentCommerceRoute
{
    /**
     * @param EntityRepository<CustomerCollection> $customerRepository
     */
    public function __construct(
        protected SalesChannelContextService $contextService,
        private readonly ShopwareCartTransformer $shopwareCartTransformer,
        private readonly CreateCartRoute $createCartRoute,
        private readonly EntityRepository $customerRepository,
        private readonly CartService $cartService
    ) {
    }

    #[Route('/api/paypal/v1/merchant-cart/{token}', name: 'api.paypal.merchant-cart.update', methods: [Request::METHOD_PUT])]
    public function updateCart(string $token, Request $request, SalesChannelContext $salesChannelContext): AgentCartResponse
    {
        CartTokenValidator::validateCartToken($token);

        $customer = $salesChannelContext->getCustomer();
        $payPalCart = (new PayPalCart())->assign($request->getPayload()->all());
        if ($customer && $payPalCart->getCustomer()) {
            $customerData = $this->shopwareCartTransformer->extractCustomerData($payPalCart, $salesChannelContext->getSalesChannelId(), $salesChannelContext->getContext());
            $customerData['id'] = $customer->getId();
            $customerData['shippingAddress']['id'] = $customer->getDefaultShippingAddress()?->getId();
            $customerData['defaultShippingAddress'] = $customerData['shippingAddress'];

            if (!isset($customerData['billingAddress']) && $customer->getDefaultBillingAddress()) {
                // TODO: TBD
            } elseif (isset($customerData['billingAddress'])) {
                $customerData['billingAddress']['id'] = $customer->getDefaultBillingAddress()?->getId() ?? Uuid::randomHex();
                $customerData['defaultBillingAddress'] = $customerData['billingAddress'];
            }

            unset($customerData['shippingAddress'], $customerData['billingAddress']);

            $this->customerRepository->update([$customerData], $salesChannelContext->getContext());

            $salesChannelContext = $this->createSalesChannelContext(
                $salesChannelContext->getToken(),
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext()
            );
        } elseif ($customer && !$payPalCart->getCustomer()) {
            $this->customerRepository->delete([['id' => $customer->getId()]], $salesChannelContext->getContext());

            $salesChannelContext = $this->createSalesChannelContext(
                $salesChannelContext->getToken(),
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext()
            );
        }

        $this->cartService->deleteCart($salesChannelContext);

        $body = \json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $request->attributes->set(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME, $body['payment_method']['token'] ?? null);

        $response = $this->createCartRoute->createCart($request, $salesChannelContext);

        if ($response->isSuccessful()) {
            if ($response->getObject()->getVars()['validation_status'] === PayPalCart::VALIDATION_STATUS__VALID) {
                $response->getObject()->assign(['validation_status' => 'READY']);
            }

            $response->setStatusCode(Response::HTTP_OK);
        }

        return $response;
    }
}
