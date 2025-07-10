<?php declare(strict_types=1);

namespace Swag\PayPal\AgentCommerce\Util;

use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RouteScopeCheckTrait;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\PlatformRequest;
use Swag\PayPal\AgentCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
class AgentDebugIDProcessor implements ProcessorInterface
{
    use RouteScopeCheckTrait;

    private Level $level;

    private RequestStack $requestStack;

    private RouteScopeRegistry $routeScopeRegistry;

    /**
     * @param int|string|Level|LogLevel::* $level
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
     */
    public function __construct(int|string|Level $level = Level::Error)
    {
        $this->level = Logger::toMonologLevel($level);
    }

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
