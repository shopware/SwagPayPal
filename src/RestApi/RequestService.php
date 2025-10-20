<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Contract\Context\ApiContextInterface;
use Shopware\PayPalSDK\Contract\RequestServiceInterface;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\RequestService as SDKRequestService;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

/**
 * Wrap ApiExceptions into PayPalApiExceptions
 */
#[Package('checkout')]
class RequestService implements RequestServiceInterface
{
    public function __construct(
        private readonly SDKRequestService $requestService
    ) {
    }

    public function createRequest(string $method, string $path, ApiContextInterface $context): RequestInterface
    {
        return $this->requestService->createRequest($method, $path, $context);
    }

    public function withBody(RequestInterface $request, array|\JsonSerializable $body): RequestInterface
    {
        return $this->requestService->withBody($request, $body);
    }

    /**
     * @throws PayPalApiException
     */
    public function handleResponse(ResponseInterface $response): ?array
    {
        try {
            return $this->requestService->handleResponse($response);
        } catch (ApiException $e) {
            throw PayPalApiException::from($e);
        }
    }
}
