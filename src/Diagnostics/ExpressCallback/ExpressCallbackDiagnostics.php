<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\ExpressCallback;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SalesChannel\SalesChannelException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\CallbackOutcome;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallback;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallbackStorage;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\StoreApiUrlStorage;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityProbe;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityResult;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\PaymentMethodUtil;

/**
 * @internal
 */
#[Package('checkout')]
class ExpressCallbackDiagnostics
{
    private const EXPRESS_BUTTON_SETTINGS = [
        Settings::ECS_DETAIL_ENABLED,
        Settings::ECS_CART_ENABLED,
        Settings::ECS_OFF_CANVAS_ENABLED,
        Settings::ECS_LOGIN_ENABLED,
        Settings::ECS_LISTING_ENABLED,
    ];

    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly LastCallbackStorage $lastCallbackStorage,
        private readonly ReachabilityProbe $reachabilityProbe,
        private readonly CallbackUrlBuilder $callbackUrlBuilder,
        private readonly StoreApiUrlStorage $storeApiUrlStorage,
        private readonly TrustedHosts $trustedHosts,
        private readonly CredentialsUtilInterface $credentialsUtil,
    ) {
    }

    public function check(string $salesChannelId, Context $context, bool $behindUntrustedProxy): ExpressCallbackResult
    {
        $salesChannel = $this->getSalesChannel($salesChannelId, $context);
        $status = $this->getStatus($salesChannel);
        $lastCallback = $this->lastCallbackStorage->get($salesChannelId);

        if ($status !== ExpressCallbackStatus::Active) {
            return new ExpressCallbackResult($status, $lastCallback, []);
        }

        return new ExpressCallbackResult($status, $lastCallback, $this->getFindings($salesChannel, $lastCallback, $behindUntrustedProxy, $context));
    }

    /**
     * @return list<ReachabilityResult>
     */
    public function testReachability(string $salesChannelId, Context $context): array
    {
        $token = Uuid::randomHex();

        return $this->reachabilityProbe->probe(
            $this->callbackUrlBuilder->getCallbackUrls($this->getSalesChannel($salesChannelId, $context), $token, $context),
            $token,
        );
    }

    private function getStatus(SalesChannelEntity $salesChannel): ExpressCallbackStatus
    {
        $payPalId = $this->paymentMethodUtil->getPaymentMethodId(PayPalMethodData::class);
        if ($payPalId === null || !$salesChannel->getPaymentMethods()?->get($payPalId)?->getActive()) {
            return ExpressCallbackStatus::Inactive;
        }

        // Headless frontends start Express Checkout without the storefront's buttons
        if ($this->isStorefront($salesChannel) && !$this->hasExpressButton($salesChannel->getId())) {
            return ExpressCallbackStatus::Inactive;
        }

        if ($this->systemConfigService->getBool(Settings::IS_LOCAL_ENVIRONMENT, $salesChannel->getId())) {
            return ExpressCallbackStatus::LocalEnvironment;
        }

        if (!$this->systemConfigService->getBool(Settings::ECS_SHIPPING_CALLBACK_ENABLED, $salesChannel->getId())) {
            return ExpressCallbackStatus::Disabled;
        }

        return ExpressCallbackStatus::Active;
    }

    /**
     * @return list<ExpressCallbackFinding>
     */
    private function getFindings(SalesChannelEntity $salesChannel, ?LastCallback $lastCallback, bool $behindUntrustedProxy, Context $context): array
    {
        $findings = [];

        if ($lastCallback?->outcome === CallbackOutcome::Failed) {
            $findings[] = new ExpressCallbackFinding(ExpressCallbackFindingType::LastCallbackFailed);
        }

        if ($behindUntrustedProxy) {
            $findings[] = new ExpressCallbackFinding(ExpressCallbackFindingType::UntrustedProxy);
        }

        if (!$this->isStorefront($salesChannel)) {
            $storeApiUrl = $this->storeApiUrlStorage->get($salesChannel->getId());

            if ($storeApiUrl === null) {
                $findings[] = new ExpressCallbackFinding(ExpressCallbackFindingType::StoreApiUrlUnknown);
            } elseif (!$this->trustedHosts->contains($storeApiUrl, $context)) {
                $findings[] = new ExpressCallbackFinding(ExpressCallbackFindingType::StoreApiHostUnknown, TrustedHosts::hostOf($storeApiUrl));
            }
        }

        if ($this->credentialsUtil->isSandbox($salesChannel->getId())) {
            $findings[] = new ExpressCallbackFinding(ExpressCallbackFindingType::Sandbox);
        }

        \usort($findings, static fn (ExpressCallbackFinding $a, ExpressCallbackFinding $b) => $a->severity->compare($b->severity));

        return $findings;
    }

    private function hasExpressButton(string $salesChannelId): bool
    {
        foreach (self::EXPRESS_BUTTON_SETTINGS as $setting) {
            if ($this->systemConfigService->getBool($setting, $salesChannelId)) {
                return true;
            }
        }

        return false;
    }

    private function isStorefront(SalesChannelEntity $salesChannel): bool
    {
        return $salesChannel->getTypeId() === Defaults::SALES_CHANNEL_TYPE_STOREFRONT;
    }

    private function getSalesChannel(string $salesChannelId, Context $context): SalesChannelEntity
    {
        $criteria = (new Criteria([$salesChannelId]))
            ->addAssociation('domains')
            ->addAssociation('paymentMethods');

        return $this->salesChannelRepository->search($criteria, $context)->getEntities()->first()
            ?? throw SalesChannelException::salesChannelNotFound($salesChannelId);
    }
}
