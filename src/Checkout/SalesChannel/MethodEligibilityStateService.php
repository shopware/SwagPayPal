<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @internal
 */
#[Package('checkout')]
class MethodEligibilityStateService
{
    public function __construct(private readonly SalesChannelContextPersister $contextPersister)
    {
    }

    /**
     * @param list<class-string> $handlers
     */
    public function setIneligiblePaymentMethods(Request $request, ?SalesChannelContext $context, array $handlers): void
    {
        $session = $this->getStartedSession($request);
        if ($session !== null) {
            $session->set(MethodEligibilityRoute::SESSION_KEY, $handlers);

            return;
        }

        if ($context === null) {
            return;
        }

        // The persister recursively merges arrays, so a scalar is required to reliably clear an empty handler list.
        $this->contextPersister->save(
            $context->getToken(),
            [MethodEligibilityRoute::SESSION_KEY => \json_encode($handlers, \JSON_THROW_ON_ERROR)],
            $context->getSalesChannelId(),
            $context->getCustomerId(),
        );
    }

    /**
     * @return list<string>
     */
    public function getIneligiblePaymentMethods(?Request $request, SalesChannelContext $context): array
    {
        if ($request === null) {
            return [];
        }

        // A started session (storefront) is the authoritative storage, even if it holds no handlers yet.
        // Falling through to the persister would add a database query to every cart validation.
        $session = $this->getStartedSession($request);
        if ($session !== null) {
            return $this->normalizeHandlers($session->get(MethodEligibilityRoute::SESSION_KEY));
        }

        $parameters = $this->contextPersister->load($context->getToken(), $context->getSalesChannelId());

        return $this->normalizeHandlers($parameters[MethodEligibilityRoute::SESSION_KEY] ?? null);
    }

    private function getStartedSession(Request $request): ?SessionInterface
    {
        if (!$request->hasSession(true)) {
            return null;
        }

        $session = $request->getSession();

        return $session->isStarted() ? $session : null;
    }

    /**
     * @return list<string>
     */
    private function normalizeHandlers(mixed $handlers): array
    {
        if (\is_string($handlers)) {
            $handlers = \json_decode($handlers, true);
        }

        if (!\is_array($handlers)) {
            return [];
        }

        return \array_values(\array_filter($handlers, \is_string(...)));
    }
}
