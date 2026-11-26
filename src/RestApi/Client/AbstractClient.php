<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Util\Log\FailureClassifier;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogRedactor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
abstract class AbstractClient
{
    protected const HEADER_WHITELIST = [
        'Paypal-Debug-Id',
        'PayPal-Request-Id',
        'Date',
    ];

    private const TOKEN_PATH_SUFFIX = '/v1/oauth2/token';

    // one message for every request, level and code tell failures apart
    private const LOG_MESSAGE = 'Requested the PayPal API: {httpMethod} {httpPath} returned {httpStatus}';

    protected ClientInterface $client;

    protected LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    protected function post(string $uri, array $options): array
    {
        return $this->request(Request::METHOD_POST, $uri, $options);
    }

    protected function get(string $uri, array $options = []): array
    {
        return $this->request(Request::METHOD_GET, $uri, $options);
    }

    protected function getHeaders(string $uri, array $options = []): array
    {
        return $this->requestHeaders(Request::METHOD_GET, $uri, $options);
    }

    protected function patch(string $uri, array $options): array
    {
        return $this->request(Request::METHOD_PATCH, $uri, $options);
    }

    protected function put(string $uri, array $options): array
    {
        return $this->request(Request::METHOD_PUT, $uri, $options);
    }

    protected function delete(string $uri, array $options = []): array
    {
        return $this->request(Request::METHOD_DELETE, $uri, $options);
    }

    private function request(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $uri, $options);
            $body = $response->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $method, $uri, $options);
        }

        $this->logRequest($method, $uri, $options, $response, $body);

        return \json_decode($body, true) ?? [];
    }

    private function requestHeaders(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $method, $uri, $options);
        }

        $this->logRequest($method, $uri, $options, $response, $response->getBody()->getContents());

        return $response->getHeaders();
    }

    private function handleRequestException(RequestException $requestException, string $method, string $uri, array $options): PayPalApiException
    {
        $exceptionMessage = $requestException->getMessage();
        $exceptionResponse = $requestException->getResponse();

        if ($exceptionResponse === null) {
            $this->logger->error('Requesting the PayPal API failed without a response: {httpMethod} {httpPath}', Log::context(
                code: LogCode::API_REQUEST_FAILED,
                httpMethod: \mb_strtoupper($method),
                httpPath: $this->getPath($uri),
                paypalRequestId: $this->getRequestId($options),
                exception: $requestException,
            ));

            return new PayPalApiException('General Error', $exceptionMessage);
        }

        $content = $exceptionResponse->getBody()->getContents();
        $error = \json_decode($content, true) ?: [];
        $issue = null;
        $issues = [];
        if (\array_key_exists('error', $error) && \array_key_exists('error_description', $error)) {
            $this->logFailure($method, $uri, $options, $exceptionResponse, $content, $error['error']);

            if ($exceptionResponse->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
                $issue = PayPalApiException::ERROR_CODE_INVALID_CREDENTIALS;
            }

            return new PayPalApiException($error['error'], $error['error_description'], $exceptionResponse->getStatusCode(), $issue);
        }

        if (\is_array($error['errors'] ?? null)) {
            $error = \current($error['errors']);
        }

        $message = $error['message'] ?? $content;

        if (isset($error['details'])) {
            $message .= ' ';
            foreach ($error['details'] as $detail) {
                if (isset($detail['description'])) {
                    $message .= \sprintf('%s ', $detail['description']);
                }
                if (isset($detail['issue'])) {
                    $issue = $detail['issue'];
                    $issues[] = $detail['issue'];
                    $message .= \sprintf('%s ', $detail['issue']);
                }
                if (isset($detail['field'])) {
                    $message .= \sprintf('(%s) ', $detail['field']);
                }
            }
        }

        $name = $error['name'] ?? 'UNCLASSIFIED_ERROR';
        $this->logFailure($method, $uri, $options, $exceptionResponse, $content, $name, $issues);

        if ($exceptionResponse->getStatusCode() === Response::HTTP_SERVICE_UNAVAILABLE) {
            return new PayPalApiException('PayPal is not available', $message, $exceptionResponse->getStatusCode(), PayPalApiException::ISSUE_NOT_AVAILABLE);
        }

        return new PayPalApiException($name, $message, $exceptionResponse->getStatusCode(), $issue);
    }

    private function logRequest(string $method, string $uri, array $options, ResponseInterface $response, string $body): void
    {
        $this->logger->debug(self::LOG_MESSAGE, Log::context(
            ...$this->getHttpContext($method, $uri, $options, $response),
            request: $this->getRequestBodyForLog($uri, $options),
            response: $this->isTokenRequest($uri) ? LogRedactor::REDACTED : $body,
        ));
    }

    /**
     * @param list<string> $issues
     */
    private function logFailure(string $method, string $uri, array $options, ResponseInterface $response, string $content, string $name, array $issues = []): void
    {
        $http = $this->getHttpContext($method, $uri, $options, $response);

        if (FailureClassifier::isMerchantAccountIssue($name, ...$issues)) {
            $this->logger->critical(self::LOG_MESSAGE, Log::context(...$http, paypalError: $name, paypalIssues: $issues, code: LogCode::API_MERCHANT_ACCOUNT_REJECTED));
        } else {
            $this->logger->info(self::LOG_MESSAGE, Log::context(...$http, paypalError: $name, paypalIssues: $issues, code: LogCode::API_REQUEST_FAILED));
        }

        $this->logger->debug(self::LOG_MESSAGE, Log::context(
            ...$http,
            request: $this->getRequestBodyForLog($uri, $options),
            response: $this->isTokenRequest($uri) ? LogRedactor::REDACTED : $content,
        ));
    }

    /**
     * @return array{httpMethod: string, httpPath: string, httpStatus: int, paypalDebugId: string, paypalRequestId: string|null}
     */
    private function getHttpContext(string $method, string $uri, array $options, ResponseInterface $response): array
    {
        return [
            Log::HTTP_METHOD => \mb_strtoupper($method),
            Log::HTTP_PATH => $this->getPath($uri),
            Log::HTTP_STATUS => $response->getStatusCode(),
            Log::PAYPAL_DEBUG_ID => $response->getHeaderLine('paypal-debug-id'),
            Log::PAYPAL_REQUEST_ID => $this->getRequestId($options),
        ];
    }

    private function getPath(string $uri): string
    {
        return \parse_url($uri, \PHP_URL_PATH) ?: $uri;
    }

    private function isTokenRequest(string $uri): bool
    {
        return \str_ends_with($this->getPath($uri), self::TOKEN_PATH_SUFFIX);
    }

    private function getRequestId(array $options): ?string
    {
        foreach ($options['headers'] ?? [] as $name => $value) {
            if (\strcasecmp((string) $name, 'paypal-request-id') === 0) {
                return \is_array($value) ? \implode(', ', $value) : (string) $value;
            }
        }

        return null;
    }

    private function getRequestBodyForLog(string $uri, array $options): string
    {
        if ($this->isTokenRequest($uri)) {
            return LogRedactor::REDACTED;
        }

        if (\array_key_exists('json', $options)) {
            return \json_encode($options['json']) ?: '';
        }

        if (\array_key_exists('form_params', $options)) {
            return \http_build_query($options['form_params']);
        }

        return (string) ($options['body'] ?? '');
    }
}
