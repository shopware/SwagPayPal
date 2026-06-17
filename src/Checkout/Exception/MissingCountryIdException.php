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
class MissingCountryIdException extends ShopwareHttpException
{
    public function __construct(
        string $orderId,
        string $code,
    ) {
        parent::__construct(
            'Shipping to the selected country is not available because "{{code}}" is not assigned to the sales channel.',
            [
                'code' => $code,
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
        return 'SWAG_PAYPAL__MISSING_COUNTRY_ID';
    }
}
