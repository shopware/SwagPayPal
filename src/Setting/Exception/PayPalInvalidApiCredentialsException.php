<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Exception;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v10.0.0 - Will be removed, use the {@see PayPalApiException} with {@see PayPalApiException::ERROR_CODE_INVALID_CREDENTIALS} directly
 */
#[Package('checkout')]
class PayPalInvalidApiCredentialsException extends PayPalApiException
{
    public function __construct()
    {
        parent::__construct(
            'invalid_client',
            'Provided API credentials are invalid',
            Response::HTTP_UNAUTHORIZED,
            PayPalApiException::ERROR_CODE_INVALID_CREDENTIALS
        );

        $this->errorCode = 'SWAG_PAYPAL__INVALID_API_CREDENTIALS';
    }
}
