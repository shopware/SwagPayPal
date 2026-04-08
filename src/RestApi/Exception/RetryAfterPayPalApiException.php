<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ApiException;

/**
 * @internal
 */
#[Package('checkout')]
class RetryAfterPayPalApiException extends PayPalApiException
{
    public function __construct(
        string $name,
        string $message,
        int $payPalApiStatusCode,
        ?string $issue,
        private readonly ?int $retryDelay,
    ) {
        parent::__construct($name, $message, $payPalApiStatusCode, $issue);
    }

    /**
     * @return int|null - Retry delay in milliseconds
     */
    public function getRetryDelay(): ?int
    {
        return $this->retryDelay;
    }

    public static function from(ApiException $e, ?string $retryAfter = null): self
    {
        ['message' => $message, 'issue' => $issue] = parent::extractMessageAndIssue($e);

        return new self(
            $e->getErrorCode(),
            $message,
            $e->getStatusCode(),
            $issue,
            self::parseRetryDelay($retryAfter),
        );
    }

    private static function parseRetryDelay(?string $retryAfter): ?int
    {
        if ($retryAfter === null || ($retryAfter = \trim($retryAfter)) === '') {
            return null;
        }

        if (\ctype_digit($retryAfter)) {
            $retryDelay = (int) $retryAfter * 1000;

            return $retryDelay > 0 ? $retryDelay : null;
        }

        $retryAt = \strtotime($retryAfter);
        if ($retryAt === false) {
            return null;
        }

        $retryDelay = ($retryAt - \time()) * 1000;

        return $retryDelay > 0 ? $retryDelay : null;
    }
}
