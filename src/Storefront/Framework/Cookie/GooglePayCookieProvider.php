<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Framework\Cookie;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Swag\PayPal\Checkout\Payment\Method\GooglePayHandler;

#[Package('checkout')]
class GooglePayCookieProvider implements CookieProviderInterface
{
    private CookieProviderInterface $original;

    /**
     * @internal
     */
    public function __construct(
        CookieProviderInterface $cookieProvider,
        private readonly EntityRepository $paymentMethodRepository,
    ) {
        $this->original = $cookieProvider;
    }

    public function getCookieGroups(): array
    {
        $cookies = $this->original->getCookieGroups();

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
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', GooglePayHandler::class));

        return $this->paymentMethodRepository->searchIds($criteria, Context::createDefaultContext())->firstId() !== null;
    }
}
