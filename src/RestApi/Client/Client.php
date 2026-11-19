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
use Swag\PayPal\Util\Log\FailureClassifier;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogKey;
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

        $path = $request->getUri()->getPath();
        $isTokenRequest = \str_ends_with($path, self::TOKEN_PATH_SUFFIX);
        $responseBody = (string) $response->getBody();
        $context = [
            LogKey::HTTP_METHOD => \mb_strtoupper($request->getMethod()),
            LogKey::HTTP_PATH => $path,
            LogKey::HTTP_STATUS => $response->getStatusCode(),
            LogKey::PAYPAL_DEBUG_ID => $response->getHeaderLine('paypal-debug-id') ?: null,
            LogKey::PAYPAL_REQUEST_ID => $request->getHeaderLine('paypal-request-id') ?: null,
        ];

        if ($response->getStatusCode() >= 400) {
            ['name' => $name, 'issues' => $issues] = $this->extractError($responseBody);
            $issue = \implode(', ', $issues) ?: $name;

            if (FailureClassifier::isMerchantAccountIssue($name, ...$issues)) {
                $this->logger->critical(
                    self::LOG_MESSAGE,
                    [...$context, LogKey::CODE => LogCode::API_MERCHANT_ACCOUNT_REJECTED, LogKey::PAYPAL_ISSUE => $issue],
                );
            } else {
                $this->logger->info(
                    self::LOG_MESSAGE,
                    [...$context, LogKey::CODE => LogCode::API_REQUEST_FAILED, LogKey::PAYPAL_ISSUE => $issue],
                );
            }
        }

        $this->logger->debug(self::LOG_MESSAGE, [
            ...$context,
            LogKey::REQUEST => $isTokenRequest ? LogRedactor::REDACTED : (string) $request->getBody(),
            LogKey::RESPONSE => $isTokenRequest ? LogRedactor::REDACTED : $responseBody,
        ]);

        return $response;
    }

    /**
     * @return array{name: string|null, issues: list<string>}
     */
    private function extractError(string $responseBody): array
    {
        $body = \json_decode($responseBody, true);
        $name = \is_array($body) ? ($body['name'] ?? $body['error'] ?? null) : null;
        $details = \is_array($body) && \is_array($body['details'] ?? null) ? $body['details'] : [];

        return [
            'name' => \is_string($name) && $name !== '' ? $name : null,
            'issues' => \array_values(\array_filter(\array_column($details, 'issue'), static fn (mixed $issue): bool => \is_string($issue) && $issue !== '')),
        ];
    }
}
