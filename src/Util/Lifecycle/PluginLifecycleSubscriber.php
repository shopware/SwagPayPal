<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Event\PluginPreUpdateEvent;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class PluginLifecycleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SwagPayPal $plugin,
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            PluginPreUpdateEvent::class => ['pluginUpdate',  \PHP_INT_MAX],
        ];
    }

    public function pluginUpdate(PluginPreUpdateEvent $event): void
    {
        if ($event->getPlugin()->getName() === 'SwagPayPal') {
            $this->plugin->patchAutoloader();
        }
    }
}
