<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Route;

use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountRegistrationService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutController;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutData;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ExpressApprovePaymentRoute extends AbstractExpressApprovePaymentRoute
{
    /**
     * @var AccountRegistrationService
     */
    private $accountRegistrationService;

    /**
     * @var EntityRepositoryInterface
     */
    private $countryRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $salutationRepo;

    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * @var SalesChannelContextFactory
     */
    private $salesChannelContextFactory;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var SalesChannelContextSwitcher
     */
    private $salesChannelContextSwitcher;

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var CartService
     */
    private $cartService;

    public function __construct(
        AccountRegistrationService $accountRegistrationService,
        EntityRepositoryInterface $countryRepo,
        EntityRepositoryInterface $salutationRepo,
        AccountService $accountService,
        SalesChannelContextFactory $salesChannelContextFactory,
        PaymentMethodUtil $paymentMethodUtil,
        SalesChannelContextSwitcher $salesChannelContextSwitcher,
        PaymentResource $paymentResource,
        CartService $cartService
    ) {
        $this->accountRegistrationService = $accountRegistrationService;
        $this->countryRepo = $countryRepo;
        $this->salutationRepo = $salutationRepo;
        $this->accountService = $accountService;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->salesChannelContextSwitcher = $salesChannelContextSwitcher;
        $this->paymentResource = $paymentResource;
        $this->cartService = $cartService;
    }

    public function getDecorated(): AbstractExpressApprovePaymentRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @OA\Post(
     *     path="/paypal/approve-payment",
     *     description="Loggs in a guest customer, with the data of a paypal payment",
     *     operationId="approvePayPalExpressPayment",
     *     tags={"Store API", "PayPal"},
     *     @OA\Parameter(
     *         parameter="paymentId",
     *         name="categoryId",
     *         in="body",
     *         description="Id of the paypal payment",
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(name="Api-Basic-Parameters"),
     *     @OA\Response(
     *         response="200",
     *         description="The new context token"
     *    )
     * )
     *
     * @RouteScope(scopes={"store-api"})
     * @Route("/store-api/v{version}/paypal/approve-payment", name="store-api.paypal.approve_payment", methods={"POST"})
     */
    public function approve(SalesChannelContext $salesChannelContext, Request $request): ContextTokenResponse
    {
        $paymentId = $request->request->get(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID);
        $payment = $this->paymentResource->get($paymentId, $salesChannelContext->getSalesChannel()->getId());
        $paypalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext());

        //Create and login a new guest customer
        $customerDataBag = $this->getCustomerDataBagFromPayment($payment, $salesChannelContext->getContext());
        $this->accountRegistrationService->register($customerDataBag, true, $salesChannelContext);
        $newContextToken = $this->accountService->login($customerDataBag->get('email'), $salesChannelContext, true);

        // Since a new customer was logged in, the context changed in the system,
        // but this doesn't effect the current context given as parameter.
        // Because of that a new context for the following operations is created
        $newSalesChannelContext = $this->salesChannelContextFactory->create(
            $newContextToken,
            $salesChannelContext->getSalesChannel()->getId()
        );

        // Set the payment method to PayPal
        $salesChannelDataBag = new DataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $paypalPaymentMethodId,
        ]);
        $this->salesChannelContextSwitcher->update($salesChannelDataBag, $newSalesChannelContext);

        $cart = $this->cartService->getCart($newSalesChannelContext->getToken(), $salesChannelContext);

        $expressCheckoutData = new ExpressCheckoutData($paymentId, $payment->getPayer()->getPayerInfo()->getPayerId());
        $cart->addExtension(ExpressCheckoutController::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID, $expressCheckoutData);

        // The cart needs to be saved.
        $this->cartService->recalculate($cart, $newSalesChannelContext);

        return new ContextTokenResponse($cart->getToken());
    }

    private function getCustomerDataBagFromPayment(Payment $payment, Context $context): DataBag
    {
        $payerInfo = $payment->getPayer()->getPayerInfo();
        $billingAddress = $payerInfo->getBillingAddress() ?? $payerInfo->getShippingAddress();
        $firstName = $payerInfo->getFirstName();
        $lastName = $payerInfo->getLastName();
        $salutationId = $this->getSalutationId($context);

        $countryId = null;
        $countryStateId = null;

        $countryCode = $billingAddress->getCountryCode();
        $country = $this->getCountryByCode($countryCode, $context);
        if ($country !== null) {
            $countryId = $country->getId();
            $countryStateId = $this->getCountryStateId(
                $billingAddress->getState(),
                $country->getStates(),
                $countryCode
            );
        }

        return new DataBag([
            'salutationId' => $salutationId,
            'email' => $payerInfo->getEmail(),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'billingAddress' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'salutationId' => $salutationId,
                'street' => $billingAddress->getLine1(),
                'zipcode' => $billingAddress->getPostalCode(),
                'countryId' => $countryId,
                'countryStateId' => $countryStateId,
                'phone' => $billingAddress->getPhone(),
                'city' => $billingAddress->getCity(),
                'additionalAddressLine1' => $billingAddress->getLine2(),
            ],
        ]);
    }

    private function getSalutationId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('salutationKey', 'not_specified')
        );

        /** @var SalutationEntity|null $salutation */
        $salutation = $this->salutationRepo->search($criteria, $context)->first();

        if ($salutation === null) {
            throw new \RuntimeException('No salutation found in Shopware');
        }

        return $salutation->getId();
    }

    private function getCountryByCode(string $code, Context $context): ?CountryEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $code));
        $criteria->addAssociation('states');
        /** @var CountryEntity|null $country */
        $country = $this->countryRepo->search($criteria, $context)->first();

        return $country;
    }

    private function getCountryStateId(
        ?string $payPalCountryStateCode,
        ?CountryStateCollection $countryStates,
        string $countryCode
    ): ?string {
        if ($payPalCountryStateCode === null) {
            return null;
        }

        if ($countryStates === null || \count($countryStates) === 0) {
            return null;
        }

        /** @var CountryStateCollection $filteredCountryStates */
        $filteredCountryStates = $countryStates->filterAndReduceByProperty(
            'shortCode',
            \sprintf('%s-%s', $countryCode, $payPalCountryStateCode)
        );
        $countryState = $filteredCountryStates->first();
        if ($countryState === null) {
            return null;
        }

        return $countryState->getId();
    }
}
