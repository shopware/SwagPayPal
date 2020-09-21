<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Exception;

use Swag\PayPal\Pos\Api\Error\PosApiError;
use Symfony\Component\HttpFoundation\Response;

class PosApiException extends PosException
{
    /**
     * @var PosApiError
     */
    private $apiError;

    public function __construct(
        PosApiError $apiError,
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR
    ) {
        $this->apiError = $apiError;
        parent::__construct($apiError->getDeveloperMessage(), $apiError->getViolationsAsString(), $statusCode);
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__POS_API_EXCEPTION_' . $this->apiError->getErrorType();
    }

    public function getApiError(): PosApiError
    {
        return $this->apiError;
    }
}
