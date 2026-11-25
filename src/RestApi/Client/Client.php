<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ErrorApiException;
use Shopware\PayPalSDK\Exception\ExceptionFactory;
use Swag\PayPal\Util\Log\FailureClassifier;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogRedactor;
use Symfony\Component\HttpClient\Psr18Client;

#[Package('checkout')]
class Client implements ClientInterface
{
    private const TOKEN_PATH_SUFFIX = '/v1/oauth2/token';

    // one message for every request, level and code tell failures apart
    private const LOG_MESSAGE = 'Requested the PayPal API: {httpMethod} {httpPath} returned {httpStatus}';

    /**
     * @internal
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private ClientInterface $client = new Psr18Client(),
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->sendRequest($request);

        $isTokenRequest = \str_ends_with($request->getUri()->getPath(), self::TOKEN_PATH_SUFFIX);
        $http = [
            Log::HTTP_METHOD => \mb_strtoupper($request->getMethod()),
            Log::HTTP_PATH => $request->getUri()->getPath(),
            Log::HTTP_STATUS => $response->getStatusCode(),
            Log::PAYPAL_DEBUG_ID => $response->getHeaderLine('paypal-debug-id'),
            Log::PAYPAL_REQUEST_ID => $request->getHeaderLine('paypal-request-id'),
        ];

        if ($response->getStatusCode() >= 400) {
            // the error the caller gets, so both lines name the same one
            $error = ExceptionFactory::createFromResponse($response);
            $issues = $error instanceof ErrorApiException ? \array_values($error->getDetails()->getIssues()) : [];

            if (FailureClassifier::isMerchantAccountIssue($error->getErrorCode(), ...$issues)) {
                $this->logger->critical(self::LOG_MESSAGE, Log::context(...$http, paypalError: $error->getErrorCode(), paypalIssues: $issues, code: LogCode::API_MERCHANT_ACCOUNT_REJECTED));
            } else {
                $this->logger->info(self::LOG_MESSAGE, Log::context(...$http, paypalError: $error->getErrorCode(), paypalIssues: $issues, code: LogCode::API_REQUEST_FAILED));
            }
        }

        $this->logger->debug(self::LOG_MESSAGE, Log::context(
            ...$http,
            request: $isTokenRequest ? LogRedactor::REDACTED : (string) $request->getBody(),
            response: $isTokenRequest ? LogRedactor::REDACTED : (string) $response->getBody(),
        ));

        return $response;
    }
}
