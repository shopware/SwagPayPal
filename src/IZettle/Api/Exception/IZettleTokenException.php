<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Exception;

use Swag\PayPal\IZettle\Api\Error\IZettleTokenError;
use Symfony\Component\HttpFoundation\Response;

class IZettleTokenException extends IZettleException
{
    /**
     * @var IZettleTokenError
     */
    private $tokenError;

    public function __construct(
        IZettleTokenError $tokenError,
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR
    ) {
        $this->tokenError = $tokenError;
        parent::__construct($tokenError->getError(), $tokenError->getErrorDescription(), $statusCode);
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__IZETTLE_TOKEN_EXCEPTION_' . $this->tokenError->getError();
    }

    public function getTokenError(): IZettleTokenError
    {
        return $this->tokenError;
    }
}
