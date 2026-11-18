<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Service;

use Psr\Http\Client\ClientExceptionInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Remembers the PayPal order a buyer approved after changing the funding instrument of their saved wallet,
 * so reloading the confirm page, e.g. after changing the address, keeps their choice.
 * A choice lasts for the session and the cart, or the order on the order edit page,
 * and only while PayPal still has the order approved, which also drops it once the order is paid.
 *
 * @internal
 */
#[Package('checkout')]
class SavedPaymentMethodEditService
{
    public const SESSION_KEY = 'swagPayPalSavedPaymentMethodEdits';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly OrderResource $orderResource,
    ) {
    }

    /**
     * @return bool Whether the PayPal order is approved and was remembered
     */
    public function remember(SalesChannelContext $salesChannelContext, ?string $orderId, string $paypalOrderId): bool
    {
        $session = $this->getSession();
        if ($session === null || !$this->isApproved($paypalOrderId, $salesChannelContext)) {
            return false;
        }

        $edits = $this->getEdits($session);
        $edits[$this->getScope($salesChannelContext, $orderId)] = $paypalOrderId;
        $session->set(self::SESSION_KEY, $edits);

        return true;
    }

    public function getApprovedOrderId(SalesChannelContext $salesChannelContext, ?string $orderId): ?string
    {
        $session = $this->getSession();
        if ($session === null) {
            return null;
        }

        $paypalOrderId = $this->getEdits($session)[$this->getScope($salesChannelContext, $orderId)] ?? null;
        if ($paypalOrderId === null) {
            return null;
        }

        if ($this->isApproved($paypalOrderId, $salesChannelContext)) {
            return $paypalOrderId;
        }

        $this->forget($salesChannelContext, $orderId);

        return null;
    }

    public function forget(SalesChannelContext $salesChannelContext, ?string $orderId): void
    {
        $session = $this->getSession();
        if ($session === null) {
            return;
        }

        $edits = $this->getEdits($session);
        unset($edits[$this->getScope($salesChannelContext, $orderId)]);
        $session->set(self::SESSION_KEY, $edits);
    }

    public function forgetAll(): void
    {
        $this->getSession()?->remove(self::SESSION_KEY);
    }

    /**
     * The storefront starts the session of a logged-in buyer anyway, so there is nothing to remember without one
     */
    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getMainRequest();
        if ($request === null || !$request->hasSession(true)) {
            return null;
        }

        return $request->getSession();
    }

    /**
     * @return array<string, string>
     */
    private function getEdits(SessionInterface $session): array
    {
        $edits = $session->get(self::SESSION_KEY);

        return \is_array($edits) ? $edits : [];
    }

    private function getScope(SalesChannelContext $salesChannelContext, ?string $orderId): string
    {
        return $orderId ?? $salesChannelContext->getToken();
    }

    private function isApproved(string $paypalOrderId, SalesChannelContext $salesChannelContext): bool
    {
        try {
            $paypalOrder = $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannelId());
        } catch (PayPalApiException|ClientExceptionInterface) {
            // PayPal drops orders that stay unpaid for a few hours
            return false;
        }

        return $paypalOrder->getStatus() === ConstantsV2::ORDER_APPROVED;
    }
}
