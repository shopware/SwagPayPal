<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\SalesChannel\Response;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RouteScopeCheckTrait;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\PlatformRequest;
use Swag\PayPal\AgenticCommerce\Exception\AgentHttpException;
use Swag\PayPal\AgenticCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgenticCommerce\Routing\AgentSource;
use Swag\PayPal\AgenticCommerce\Struct\V1\AgentError;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('checkout')]
class AgentResponseExceptionSubscriber implements EventSubscriberInterface
{
    use RouteScopeCheckTrait;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RouteScopeRegistry $routeScopeRegistry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', 0],
            ],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$this->isRequestScoped($event->getRequest(), AgentRouteScope::class)) {
            return;
        }

        $exception = $event->getThrowable();

        $this->logger->error($exception->getMessage(), ['exception' => $exception]);

        $source = self::extractSource($event);
        $response = new JsonResponse($this->getResponseFromException($exception, $source));

        $event->setResponse($response);
        $event->stopPropagation();
    }

    protected function getScopeRegistry(): RouteScopeRegistry
    {
        return $this->routeScopeRegistry;
    }

    private function getResponseFromException(\Throwable $exception, ?AgentSource $source = null): AgentError
    {
        $error = new AgentError();
        $error->setName('UNKNOWN_ERROR');
        $error->setCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        $error->setMessage($exception->getMessage());

        if ($source) {
            $error->setDebugId($source->debugId);
        }

        if ($exception instanceof HttpException) {
            $error->setName($exception->getErrorCode());
            $error->setCode($exception->getStatusCode());

            if ($exception instanceof AgentHttpException) {
                $error->setDetails($exception->getDetails());
            }
        }

        return $error;
    }

    private static function extractSource(ExceptionEvent $event): ?AgentSource
    {
        $context = $event->getRequest()->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);

        if (!$context instanceof Context) {
            return null;
        }

        if (!$context->getSource() instanceof AgentSource) {
            return null;
        }

        return $context->getSource();
    }
}
