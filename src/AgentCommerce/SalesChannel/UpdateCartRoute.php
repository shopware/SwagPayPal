<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Swag\PayPal\AgentCommerce\Util\ShopwareCartTransformer;
use Swag\PayPal\AgentCommerce\Validation\CartTokenValidator;
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
     * @param EntityRepository<CustomerAddressCollection> $customerAddressRepository
     */
    public function __construct(
        protected SalesChannelContextService $contextService,
        private readonly ShopwareCartTransformer $shopwareCartTransformer,
        private readonly CreateCartRoute $createCartRoute,
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $customerAddressRepository,
        private readonly CartService $cartService,
        private readonly ContextSwitchRoute $contextSwitchRoute,
    ) {
    }

    #[Route('/api/paypal/v1/merchant-cart/{token}', name: 'api.paypal.merchant-cart.update', methods: [Request::METHOD_PUT])]
    public function updateCart(string $token, Request $request, SalesChannelContext $salesChannelContext): AgentCartResponse
    {
        CartTokenValidator::validateCartToken($token);

        $payPalCart = (new PayPalCart())->assign($request->getPayload()->all());
        $salesChannelContext = $this->loginCustomer($payPalCart, $salesChannelContext);

        $this->cartService->deleteCart($salesChannelContext);

        $salesChannelContext = $this->changeShippingMethod($payPalCart, $salesChannelContext);

        $response = $this->createCartRoute->createCart($request, $salesChannelContext);

        if ($response->isSuccessful()) {
            if ($response->getObject()->offsetGet('validation_status') === PayPalCart::VALIDATION_STATUS__VALID) {
                $response->getObject()->offsetSet('validation_status', PayPalCart::STATUS__READY);
            }

            $response->setStatusCode(Response::HTTP_OK);
        }

        return $response;
    }

    private function loginCustomer(PayPalCart $payPalCart, SalesChannelContext $salesChannelContext): SalesChannelContext
    {
        $customer = $salesChannelContext->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            return $salesChannelContext;
        }

        if (!$payPalCart->getCustomer()) {
            $this->customerRepository->delete([['id' => $customer->getId()]], $salesChannelContext->getContext());

            return $this->createSalesChannelContext(
                $salesChannelContext->getToken(),
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext()
            );
        }

        $customerData = $this->shopwareCartTransformer->extractCustomerData($payPalCart, $salesChannelContext->getSalesChannelId(), $salesChannelContext);
        $customerData['id'] = $customer->getId();
        $customerData['shippingAddress']['id'] = $customer->getDefaultShippingAddressId();
        $customerData['defaultShippingAddress'] = $customerData['shippingAddress'];

        $toDeleteAddress = null;
        if (isset($customerData['billingAddress'])) {
            $customerData['billingAddress']['id'] = $customer->getDefaultBillingAddressId();
            $customerData['defaultBillingAddress'] = $customerData['billingAddress'];
        } elseif ($customer->getDefaultShippingAddressId() !== $customer->getDefaultBillingAddressId()) {
            $toDeleteAddress = [['id' => $customer->getDefaultBillingAddressId()]];

            $customerData['defaultBillingAddressId'] = $customer->getDefaultShippingAddressId();
        }

        unset($customerData['shippingAddress'], $customerData['billingAddress']);

        $this->customerRepository->update([$customerData], $salesChannelContext->getContext());

        if (!empty($toDeleteAddress)) {
            $this->customerAddressRepository->delete($toDeleteAddress, $salesChannelContext->getContext());
        }

        return $this->createSalesChannelContext(
            $salesChannelContext->getToken(),
            $salesChannelContext->getSalesChannelId(),
            $salesChannelContext->getContext()
        );
    }

    private function changeShippingMethod(PayPalCart $payPalCart, SalesChannelContext $salesChannelContext): SalesChannelContext
    {
        $shippingOptions = $payPalCart->getAvailableShippingOptions();
        if (!$shippingOptions) {
            return $salesChannelContext;
        }

        foreach ($shippingOptions as $shippingOption) {
            if (!$shippingOption->isSelected()) {
                continue;
            }

            if ($shippingOption->getId() === $salesChannelContext->getShippingMethod()->getId()) {
                // Right shipping method already selected
                break;
            }

            try {
                $token = $this->contextSwitchRoute->switchContext(new RequestDataBag([SalesChannelContextService::SHIPPING_METHOD_ID => $shippingOption->getId()]), $salesChannelContext)->getToken();
            } catch (ConstraintViolationException $e) {
                throw AgentException::requiredFieldInvalid('availableShippingOption.id', $e->getViolations()->__toString());
            }

            return $this->createSalesChannelContext(
                $token,
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext()
            );
        }

        return $salesChannelContext;
    }
}
