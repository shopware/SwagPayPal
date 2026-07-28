<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Util;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RouteScopeCheckTrait;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\PlatformRequest;
use Swag\PayPal\AgenticCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgenticCommerce\Routing\AgentSource;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
class AgentDebugIDProcessor implements ProcessorInterface
{
    use RouteScopeCheckTrait;

    private RequestStack $requestStack;

    private RouteScopeRegistry $routeScopeRegistry;

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request || !$this->isRequestScoped($request, AgentRouteScope::class)) {
            return $record;
        }

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);

        if (!$context instanceof Context) {
            return $record;
        }

        $source = $context->getSource();

        if (!$source instanceof AgentSource) {
            return $record;
        }

        if (!$source->debugId) {
            return $record;
        }

        $record->extra['debugId'] = $source->debugId;

        return $record;
    }

    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    public function setRouteScopeRegistry(RouteScopeRegistry $routeScopeRegistry): void
    {
        $this->routeScopeRegistry = $routeScopeRegistry;
    }

    protected function getScopeRegistry(): RouteScopeRegistry
    {
        return $this->routeScopeRegistry;
    }
}
