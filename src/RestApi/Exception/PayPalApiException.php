<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Exception;

use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PayPalApiException extends PaymentException
{
    public const ERROR_CODE_INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    public const ERROR_CODE_DUPLICATE_ORDER_NUMBER = 'DUPLICATE_TRANSACTION';
    /**
     * @deprecated tag:v10.0.0 - will be replaced with {@see ISSUE_DUPLICATE_INVOICE_ID}
     */
    public const ERROR_CODE_DUPLICATE_INVOICE_ID = self::ISSUE_DUPLICATE_INVOICE_ID;
    public const ISSUE_DUPLICATE_INVOICE_ID = 'DUPLICATE_INVOICE_ID';
    /**
     * @deprecated tag:v10.0.0 - will be replaced with {@see ISSUE_INVALID_PARAMETER_VALUE}
     */
    public const ERROR_CODE_INVALID_PARAMETER_VALUE = self::ISSUE_INVALID_PARAMETER_VALUE;
    public const ISSUE_INVALID_PARAMETER_VALUE = 'INVALID_PARAMETER_VALUE';
    public const ERROR_CODE_RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
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

    /**
     * @deprecated tag:v10.0.0 - will be removed with Shopware 6.7 compatible version
     */
    public function setOrderTransactionId(string $orderTransactionId): void
    {
        $this->parameters['orderTransactionId'] = $orderTransactionId;
    }
}
