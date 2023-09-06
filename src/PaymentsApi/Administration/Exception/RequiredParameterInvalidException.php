<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Administration\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
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
