<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook\Exception;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class WebhookHandlerNotFoundException extends WebhookException
{
    public function __construct(
        string $eventType,
        array $parameters = [],
    ) {
        $message = \sprintf('No webhook handler found for event "%s". Shopware does not need to handle this event.', $eventType);
        parent::__construct($eventType, $message, $parameters);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
