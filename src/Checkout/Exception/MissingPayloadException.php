<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class MissingPayloadException extends ShopwareHttpException
{
    public function __construct(
        string $orderId,
        string $path,
    ) {
        parent::__construct(
            'Missing request payload {{ path }} to order "{{ orderId }}" not found',
            [
                'path' => $path,
                'orderId' => $orderId,
            ]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__MISSING_REQUEST_PAYLOAD';
    }
}
