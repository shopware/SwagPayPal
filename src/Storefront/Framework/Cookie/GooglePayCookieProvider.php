<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Framework\Cookie;

use Shopware\Core\Content\Cookie\Event\CookieGroupCollectEvent;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\PlatformRequest;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Swag\PayPal\Checkout\Payment\Method\GooglePayHandler;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @deprecated tag:v11.0.0 - Will be removed. Use {@see CookieGroupCollectEvent} instead to introduce cookies.
 */
#[Package('checkout')]
class GooglePayCookieProvider implements CookieProviderInterface
{
    /**
     * @internal
     *
     * @deprecated tag:v11.0.0 - Will be removed. Use {@see CookieGroupCollectEvent} instead to introduce cookies.
     */
    public function __construct(
        private CookieProviderInterface $cookieProvider,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed. Use {@see CookieGroupCollectEvent} instead to introduce cookies.
     */
    public function getCookieGroups(): array
    {
        $cookies = $this->cookieProvider->getCookieGroups();
        if (\class_exists(CookieGroupCollectEvent::class)) {
            return $cookies;
        }

        foreach ($cookies as &$cookie) {
            if (!\is_array($cookie)) {
                continue;
            }

            if (!$this->isRequiredCookieGroup($cookie)) {
                continue;
            }

            if (!\array_key_exists('entries', $cookie)) {
                continue;
            }

            if ($this->isGooglePayActive()) {
                $cookie['entries'][] = [
                    'snippet_name' => 'paypal.cookie.googlePay',
                    'cookie' => 'paypal-google-pay-cookie-key',
                ];
            }
        }

        return $cookies;
    }

    private function isRequiredCookieGroup(array $cookie): bool
    {
        return (\array_key_exists('isRequired', $cookie) && $cookie['isRequired'] === true)
            && (\array_key_exists('snippet_name', $cookie) && $cookie['snippet_name'] === 'cookie.groupRequired');
    }

    private function isGooglePayActive(): bool
    {
        $salesChannelContext = $this->requestStack->getMainRequest()?->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        if (!$salesChannelContext) {
            return false;
        }

        return $this->paymentMethodUtil->isPaymentMethodActive($salesChannelContext, [GooglePayHandler::class]);
    }
}
