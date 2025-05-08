<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Exception;

use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Exception\ErrorApiException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PayPalApiException extends PaymentException
{
    public const ERROR_CODE_INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    public const ERROR_CODE_DUPLICATE_ORDER_NUMBER = 'DUPLICATE_TRANSACTION';
    public const ERROR_CODE_RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';

    public const ISSUE_NOT_AVAILABLE = 'NOT_AVAILABLE';
    public const ISSUE_DUPLICATE_INVOICE_ID = 'DUPLICATE_INVOICE_ID';
    public const ISSUE_INVALID_PARAMETER_VALUE = 'INVALID_PARAMETER_VALUE';
    public const ISSUE_INVALID_RESOURCE_ID = 'INVALID_RESOURCE_ID';

    /**
     * @param string $name - The general name of the error, groups multiple issues
     * @param string|null $issue - The specific issue which caused the error
     */
    public function __construct(
        private string $name,
        string $message,
        int $payPalApiStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        private ?string $issue = null,
    ) {
        parent::__construct(
            $payPalApiStatusCode,
            'SWAG_PAYPAL__API_' . ($issue ?? 'EXCEPTION'),
            'The error "{{ name }}" occurred with the following message: {{ message }}',
            [
                'name' => $name,
                'message' => $message,
                'issue' => $issue,
            ]
        );
    }

    /**
     * @return string - The general name of the error, groups multiple issues
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null - The specific issue which caused the error
     */
    public function getIssue(): ?string
    {
        return $this->issue;
    }

    /**
     * Is error code or issue one of the given codes/issues?
     *
     * @param self::ERROR_CODE_*|self::ISSUE_*|string $codes
     */
    public function is(string ...$codes): bool
    {
        return \in_array($this->errorCode, $codes, true)
            || \in_array($this->issue, $codes, true)
            || \in_array($this->name, $codes, true);
    }

    public static function from(ApiException $e): self
    {
        $message = $e->getReason();
        $issue = null;

        if ($e instanceof ErrorApiException && ($detailMessage = (string) $e->getDetails())) {
            $message .= ApiException::MESSAGE_DELIMITER . $detailMessage;

            $issue = \array_slice($e->getDetails()->getIssues(), -1)[0] ?? null;
        }

        return new self(
            $e->getErrorCode(),
            $message,
            $e->getStatusCode(),
            $issue,
        );
    }
}
