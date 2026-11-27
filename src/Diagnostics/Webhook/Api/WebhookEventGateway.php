<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook\Api;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Contract\Context\ApiContextInterface;
use Shopware\PayPalSDK\Gateway\AbstractGateway;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookEventGateway extends AbstractGateway
{
    public const EVENTS_URL = '/v1/notifications/webhooks-events';
    public const SIMULATE_URL = '/v1/notifications/simulate-event';

    private const TIME_FORMAT = 'Y-m-d\\TH:i:s\\Z';

    /**
     * Newest first. Whether the events were delivered isn't part of the answer.
     */
    public function getEvents(ApiContextInterface $context, \DateTimeInterface $startTime, \DateTimeInterface $endTime, int $pageSize): WebhookEventList
    {
        $context = $context
            ->withQueryParameter('page_size', (string) $pageSize)
            ->withQueryParameter('start_time', self::formatTime($startTime))
            ->withQueryParameter('end_time', self::formatTime($endTime));

        return $this->request('GET', self::EVENTS_URL, null, WebhookEventList::class, $context);
    }

    public function simulateEvent(SimulateEvent $body, ApiContextInterface $context): Event
    {
        return $this->request('POST', self::SIMULATE_URL, $body, Event::class, $context);
    }

    private static function formatTime(\DateTimeInterface $time): string
    {
        return \DateTimeImmutable::createFromInterface($time)->setTimezone(new \DateTimeZone('UTC'))->format(self::TIME_FORMAT);
    }
}
