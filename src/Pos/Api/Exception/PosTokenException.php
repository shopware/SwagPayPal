<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Exception;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Error\PosTokenError;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PosTokenException extends PosException
{
    private PosTokenError $tokenError;

    public function __construct(
        PosTokenError $tokenError,
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
    ) {
        $this->tokenError = $tokenError;
        parent::__construct($tokenError->getError(), $tokenError->getErrorDescription(), $statusCode);
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__POS_TOKEN_EXCEPTION_' . $this->tokenError->getError();
    }

    public function getTokenError(): PosTokenError
    {
        return $this->tokenError;
    }
}
