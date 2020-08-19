<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Webhook\_fixtures;

use Swag\PayPal\IZettle\Webhook\WebhookEventNames;

class TestMessageFixture
{
    public static function getWebhookFixture(): array
    {
        return [
            'organizationUuid' => '1ce52f60-645f-11ea-a80e-0cec44ada668',
            'messageUuid' => 'f2601720-dc66-11ea-9c1c-05298cb0156a',
            'eventName' => WebhookEventNames::TEST_MESSAGE,
            'messageId' => '6e6d5284-1d31-544f-9c1c-05298cb0156a',
            'payload' => \json_encode(['data' => 'payload']),
            'timestamp' => '2020-08-12T06:42:09.938Z',
        ];
    }
}
