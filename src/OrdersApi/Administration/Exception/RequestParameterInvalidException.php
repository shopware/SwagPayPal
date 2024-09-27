<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Administration\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class RequestParameterInvalidException extends ShopwareHttpException
{
    public function __construct(
        string $invalidParameter,
        string $additionalInfo = '',
    ) {
        $message = 'Parameter "{{ invalidParameter }}" is invalid.';
        if ($additionalInfo !== '') {
            $message .= \PHP_EOL . $additionalInfo;
        }
        parent::__construct(
            $message,
            ['invalidParameter' => $invalidParameter]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__REQUEST_PARAMETER_INVALID';
    }
}
