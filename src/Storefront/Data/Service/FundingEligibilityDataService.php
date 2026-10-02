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
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityStateService;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Storefront\Data\Struct\FundingEligibilityData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
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
        RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly MethodEligibilityStateService $methodEligibilityStateService,
        private readonly PaymentMethodUtil $paymentMethodUtil,
    ) {
        parent::__construct($localeCodeProvider, $systemConfigService, $credentialsUtil, $router);
    }

    public function buildData(SalesChannelContext $context): ?FundingEligibilityData
    {
        return (new FundingEligibilityData())->assign([
            ...parent::getBaseData($context),
            'methodEligibilityUrl' => $this->router->generate('frontend.paypal.payment-method-eligibility'),
            'filteredPaymentMethods' => $this->getFilteredPaymentMethods($context),
            // @deprecated tag:v11.0.0 - Will be removed, SEPA eligibility will be checked via SDK v6.
            'sepaActive' => $this->paymentMethodUtil->isPaymentMethodActive($context, [SEPAHandler::class]),
        ]);
    }

    private function getFilteredPaymentMethods(SalesChannelContext $context): array
    {
        $handlers = $this->methodEligibilityStateService->getIneligiblePaymentMethods(
            $this->requestStack->getCurrentRequest(),
            $context,
        );

        return \array_keys(\array_filter(\array_intersect(
            MethodEligibilityRoute::REMOVABLE_PAYMENT_HANDLERS,
            $handlers,
        )));
    }
}
