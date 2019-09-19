<?php declare(strict_types=1);

namespace Swag\PayPal\Payment\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class RequiredParameterInvalidException extends ShopwareHttpException
{
    public function __construct(string $missingParameter)
    {
        parent::__construct(
            'Required parameter "{{ missingParameter }}" is missing or invalid',
            ['missingParameter' => $missingParameter]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__REQUIRED_PARAMETER_INVALID';
    }
}
