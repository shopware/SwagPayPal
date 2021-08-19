<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OpenApi;

use Shopware\Core\Framework\Api\ApiDefinition\Generator\OpenApi\Event\OpenApiPathsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OpenApiPathsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [OpenApiPathsEvent::class => 'addSchemaPaths'];
    }

    public function addSchemaPaths(OpenApiPathsEvent $event): void
    {
        $event->addPath(__DIR__ . '/../RestApi/V1');
        $event->addPath(__DIR__ . '/../RestApi/V2');
    }
}
