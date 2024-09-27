<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Storefront\Data\Struct\FundingEligibilityData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class FundingEligibilityDataService extends AbstractScriptDataService
{
    /**
     * @internal
     */
    public function __construct(
        CredentialsUtilInterface $credentialsUtil,
        SystemConfigService $systemConfigService,
        LocaleCodeProvider $localeCodeProvider,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct($localeCodeProvider, $systemConfigService, $credentialsUtil);
    }

    public function buildData(SalesChannelContext $context): ?FundingEligibilityData
    {
        return (new FundingEligibilityData())->assign([
            ...parent::getBaseData($context),
            'methodEligibilityUrl' => $this->router->generate('frontend.paypal.payment-method-eligibility'),
            'filteredPaymentMethods' => $this->getFilteredPaymentMethods(),
        ]);
    }

    private function getFilteredPaymentMethods(): array
    {
        $handlers = $this->requestStack->getSession()->get(MethodEligibilityRoute::SESSION_KEY, []);
        if (!$handlers) {
            return [];
        }

        $methods = [];
        foreach ($handlers as $handler) {
            $methods[] = \array_search($handler, MethodEligibilityRoute::REMOVABLE_PAYMENT_HANDLERS, true);
        }

        return \array_filter($methods);
    }
}
