<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\KernelListenerPriorities;
use Shopware\Core\Framework\Routing\RequestContextResolverInterface;
use Swag\PayPal\AgenticCommerce\Routing\AgentContextResolverListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentContextResolverListener::class)]
class AgentContextResolverListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        static::assertSame(
            [
                KernelEvents::CONTROLLER => [
                    ['resolveContext', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_CONTEXT_RESOLVE],
                ],
            ],
            AgentContextResolverListener::getSubscribedEvents()
        );
    }

    public function testResolveContext(): void
    {
        $request = new Request();
        $event = new ControllerEvent($this->createMock(HttpKernelInterface::class), static function (): void {}, $request, HttpKernelInterface::MAIN_REQUEST);

        $resolver = $this->createMock(RequestContextResolverInterface::class);
        $resolver
            ->expects(static::once())
            ->method('resolve')
            ->with($request);

        $listener = new AgentContextResolverListener($resolver);
        $listener->resolveContext($event);
    }
}
