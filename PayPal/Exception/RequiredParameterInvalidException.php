<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class RequiredParameterInvalidException extends ShopwareHttpException
{
    protected $code = 'SWAG-PAYPAL-REQUIRED-PARAMETER-INVALID';

    public function __construct(string $missingParameter, $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('Required parameter "%s" is missing or invalid', $missingParameter);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
