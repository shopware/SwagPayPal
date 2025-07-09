<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel\Response;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\PlatformRequest;
use Shopware\PayPalSDK\Struct\AgenticCommerceV1\AgentError;
use Swag\PayPal\AgentCommerce\Exception\AgentHttpException;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
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
        if (!$event->getRequest()->attributes->has(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT)) {
            return;
        }

        $context = $event->getRequest()->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);

        if (!$context instanceof Context) {
            return;
        }

        if (!$context->getSource() instanceof AgentSource) {
            return;
        }

        $exception = $event->getThrowable();
        $response = new JsonResponse(self::getResponseFromException($exception, $context->getSource()));

        $event->setResponse($response);
        $event->stopPropagation();
    }

    private static function getResponseFromException(\Throwable $exception, AgentSource $source): AgentError
    {
        $error = new AgentError();

        if ($exception instanceof HttpException) {
            $error->assign([
                'name' => $exception->getErrorCode(),
                'code' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
                'debugId' => $source->debugId,
            ]);

            if ($exception instanceof AgentHttpException) {
                $error->setDetails($exception->getDetails());
            }

            return $error;
        }

        $error->assign([
            'name' => 'UNKNOWN_ERROR',
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $exception->getMessage(),
            'debugId' => $source->debugId,
        ]);

        return $error;
    }
}
