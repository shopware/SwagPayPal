<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\Webhook\Registration\WebhookUrlSource;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_webhook_candidate_url',
    description: 'A URL the webhook could be registered for. The token is replaced by `<token>`',
    required: ['source', 'url'],
    properties: [
        new OA\Property(property: 'source', description: 'Where the host of the URL comes from: the host the Administration is opened with, or Shopware\'s `APP_URL`', type: 'string', enum: ['administration', 'app_url']),
        new OA\Property(property: 'url', type: 'string'),
    ],
)]
#[Package('checkout')]
final class WebhookCandidateUrl extends Struct
{
    public function __construct(
        public readonly WebhookUrlSource $source,
        public readonly string $url,
    ) {
    }
}
