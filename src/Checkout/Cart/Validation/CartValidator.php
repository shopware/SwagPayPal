<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Cart\Validation;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Util\Availability\AvailabilityService;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

#[Package('checkout')]
class CartValidator implements CartValidatorInterface
{
    private CartPriceService $cartPriceService;

    private PaymentMethodDataRegistry $methodDataRegistry;

    private SettingsValidationServiceInterface $settingsValidationService;

    private RequestStack $requestStack;

    private ExcludedProductValidator $excludedProductValidator;

    private AvailabilityService $availabilityService;

    /**
     * @internal
     */
    public function __construct(
        CartPriceService $cartPriceService,
        PaymentMethodDataRegistry $methodDataRegistry,
        SettingsValidationServiceInterface $settingsValidationService,
        RequestStack $requestStack,
        ExcludedProductValidator $excludedProductValidator,
        AvailabilityService $availabilityService,
    ) {
        $this->cartPriceService = $cartPriceService;
        $this->methodDataRegistry = $methodDataRegistry;
        $this->settingsValidationService = $settingsValidationService;
        $this->requestStack = $requestStack;
        $this->excludedProductValidator = $excludedProductValidator;
        $this->availabilityService = $availabilityService;
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        if (!$this->methodDataRegistry->isPayPalPaymentMethod($context->getPaymentMethod())) {
            return;
        }

        $name = (string) $context->getPaymentMethod()->getTranslation('name');
        $id = $context->getPaymentMethod()->getId();

        try {
            $this->settingsValidationService->validate($context->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $e) {
            /** @deprecated tag:v11.0.0 - The order of parameters will be changed to: $id, $name, $reason */
            $errors->add(new PaymentMethodBlockedError($name, 'not available', $id));

            return;
        }

        if ($this->cartPriceService->isZeroValueCart($cart)) {
            /** @deprecated tag:v11.0.0 - The order of parameters will be changed to: $id, $name, $reason */
            $errors->add(new PaymentMethodBlockedError($name, 'zero value cart', $id));

            return;
        }

        try {
            $ineligiblePaymentMethods = $this->requestStack->getSession()->get(MethodEligibilityRoute::SESSION_KEY);
            if (\is_array($ineligiblePaymentMethods) && \in_array($context->getPaymentMethod()->getHandlerIdentifier(), $ineligiblePaymentMethods, true)) {
                /** @deprecated tag:v11.0.0 - The order of parameters will be changed to: $id, $name, $reason */
                $errors->add(new PaymentMethodBlockedError($name, 'ineligible', $id));

                return;
            }
        } catch (SessionNotFoundException $e) {
            return;
        }

        if ($this->excludedProductValidator->cartContainsExcludedProduct($cart, $context)) {
            /** @deprecated tag:v11.0.0 - The order of parameters will be changed to: $id, $name, $reason */
            $errors->add(new PaymentMethodBlockedError($name, 'excluded', $id));

            return;
        }

        if (!$this->availabilityService->isPaymentMethodAvailable($context->getPaymentMethod(), $cart, $context)) {
            /** @deprecated tag:v11.0.0 - The order of parameters will be changed to: $id, $name, $reason */
            $errors->add(new PaymentMethodBlockedError($name, 'not available', $id));
        }
    }
}
