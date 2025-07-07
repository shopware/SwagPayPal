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
use Swag\PayPal\AgentCommerce\Exception\PayPalAgentErrorResponse;
use Swag\PayPal\AgentCommerce\Exception\PayPalAgentHttpException;
use Swag\PayPal\AgentCommerce\Routing\PayPalAgentSource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalAgentResponseExceptionSubscriber implements EventSubscriberInterface
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
        if (!$event->getRequest()->attributes->has('sw-context')) {
            return;
        }

        $context = $event->getRequest()->attributes->get('sw-context');

        if (!$context instanceof Context) {
            return;
        }

        if (!$context->getSource() instanceof PayPalAgentSource) {
            return;
        }

        $exception = $event->getThrowable();

        if (!$exception instanceof HttpException) {
            return;
        }

        $event->setResponse($this->getResponseFromException($exception, $context->getSource()));
    }

    private function getResponseFromException(HttpException $exception, PayPalAgentSource $source): PayPalAgentErrorResponse
    {
        return new PayPalAgentErrorResponse(
            $exception->getErrorCode(),
            $exception->getStatusCode(),
            $exception->getMessage(),
            $source->debugId,
            $exception instanceof PayPalAgentHttpException ? $exception->getDetails() : [],
        );
    }
}
