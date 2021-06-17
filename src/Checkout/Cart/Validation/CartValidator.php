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
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\PayPalPuiPaymentHandler;

class CartValidator implements CartValidatorInterface
{
    private CartPriceService $cartPriceService;

    public function __construct(CartPriceService $cartPriceService)
    {
        $this->cartPriceService = $cartPriceService;
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        if (!$this->cartPriceService->isZeroValueCart($cart)) {
            return;
        }

        if ($context->getPaymentMethod()->getHandlerIdentifier() !== PayPalPaymentHandler::class
            && $context->getPaymentMethod()->getHandlerIdentifier() !== PayPalPuiPaymentHandler::class) {
            return;
        }

        $errors->add(
            new PaymentMethodBlockedError((string) $context->getPaymentMethod()->getTranslation('name'))
        );
    }
}
