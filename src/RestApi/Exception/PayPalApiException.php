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
    public const ERROR_CODE_DUPLICATE_INVOICE_ID = 'DUPLICATE_INVOICE_ID';
    public const ERROR_CODE_RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const ERROR_CODE_INVALID_PARAMETER_VALUE = 'INVALID_PARAMETER_VALUE';

    public function __construct(
        string $name,
        string $message,
        int $payPalApiStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        private ?string $issue = null
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

    public function getIssue(): ?string
    {
        return $this->issue;
    }

    /**
     * Is error code or issue one of the given codes/issues?
     *
     * @param self::ERROR_CODE_*|string $codes
     */
    public function is(string ...$codes): bool
    {
        return \in_array($this->errorCode, $codes, true)
            || \in_array($this->issue, $codes, true);
    }

    public function setOrderTransactionId(string $orderTransactionId): void
    {
        $this->parameters['orderTransactionId'] = $orderTransactionId;
    }
}
