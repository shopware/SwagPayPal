<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Exception;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Webhook\Recording\WebhookOutcome;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookRejectedException extends BadRequestHttpException
{
    public function __construct(
        public readonly WebhookOutcome $outcome,
        \Throwable $previous,
    ) {
        parent::__construct('An error occurred during execution of webhook', $previous);
    }
}
