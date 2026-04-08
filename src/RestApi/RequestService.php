<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi;

use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\RequestService as SDKRequestService;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\Exception\RetryAfterPayPalApiException;

/**
 * Wrap ApiExceptions into PayPalApiExceptions
 */
#[Package('checkout')]
class RequestService extends SDKRequestService
{
    /**
     * @throws PayPalApiException
     */
    public function handleResponse(ResponseInterface $response): ?array
    {
        try {
            return parent::handleResponse($response);
        } catch (ApiException $e) {
            if ($e->is(PayPalApiException::ERROR_CODE_RATE_LIMIT_REACHED)) {
                throw RetryAfterPayPalApiException::from($e, $response->getHeaderLine('Retry-After') ?: null);
            }

            throw PayPalApiException::from($e);
        }
    }
}
