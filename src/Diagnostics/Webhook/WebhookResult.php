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
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhook;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationStatus;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_webhook_result',
    required: ['registration', 'lastWebhook', 'registeredUrl', 'candidateUrls', 'missedEvents', 'findings'],
    properties: [
        new OA\Property(property: 'registration', description: '`invalid` when the webhook is registered for another URL, `disabled` when the "Local development environment" is on, `unknown` when PayPal couldn\'t be asked', type: 'string', enum: ['valid', 'invalid', 'missing', 'disabled', 'unknown']),
        new OA\Property(property: 'lastWebhook', ref: LastWebhook::class, nullable: true),
        new OA\Property(property: 'registeredUrl', description: 'The URL the webhook is registered for at PayPal, only when the registration is `invalid`. The token is replaced by `<token>`', type: 'string', nullable: true),
        new OA\Property(property: 'candidateUrls', description: 'The URLs the webhook could be registered for, only when the registration is `invalid`: one per distinct URL, the Administration\'s first', type: 'array', items: new OA\Items(ref: WebhookCandidateUrl::class)),
        new OA\Property(property: 'missedEvents', ref: MissedEvents::class, nullable: true),
        new OA\Property(property: 'findings', description: 'Empty when the registration is `disabled`', type: 'array', items: new OA\Items(ref: WebhookFinding::class)),
    ],
)]
#[Package('checkout')]
final class WebhookResult extends Struct
{
    /**
     * @param list<WebhookFinding> $findings
     * @param list<WebhookCandidateUrl> $candidateUrls
     */
    public function __construct(
        public readonly WebhookRegistrationStatus $registration,
        public readonly ?LastWebhook $lastWebhook,
        public readonly array $findings,
        public readonly ?string $registeredUrl = null,
        public readonly array $candidateUrls = [],
        public readonly ?MissedEvents $missedEvents = null,
    ) {
    }
}
