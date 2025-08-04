<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Exception;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Struct\V1\AgentErrorDetail;
use Swag\PayPal\AgentCommerce\Struct\V1\AgentErrorDetailCollection;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class AgentException extends AgentHttpException
{
    public const INVALID_REQUEST = 'INVALID_REQUEST';
    public const INVALID_CART_ID = 'INVALID_CART_ID';
    public const CART_NOT_FOUND = 'CART_NOT_FOUND';
    public const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    public const SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';
    public const PAYMENT_PROCESSOR_UNAVAILABLE = 'PAYMENT_PROCESSOR_UNAVAILABLE';
    public const PAYMENT_CAPTURE_FAILED = 'PAYMENT_CAPTURE_FAILED';
    public const INVENTORY_SYSTEM_ERROR = 'INVENTORY_SYSTEM_ERROR';
    public const ORDER_SYSTEM_ERROR = 'ORDER_SYSTEM_ERROR';

    public static function requiredFieldsMissing(string ...$fields): self
    {
        $message = 'Required field \'{{ fields }}\' is missing';
        $parameters = ['fields' => implode(', ', $fields)];
        $details = new AgentErrorDetailCollection();

        foreach ($fields as $field) {
            $detail = (new AgentErrorDetail());
            $detail->setField($field);
            $detail->setIssue('MISSING_REQUIRED_FIELD');
            $detail->setDescription(\sprintf('The field \'%s\' is required and cannot be empty', $field));

            $details->add($detail);
        }

        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_REQUEST,
            $message,
            $parameters,
            $details
        );
    }

    public static function requiredFieldInvalid(string $field, string $reason): self
    {
        $message = 'Required field \'{{ field }}\' is invalid: \'{{ reason }}\'';
        $parameters = ['field' => $field, 'reason' => $reason];

        $detail = new AgentErrorDetail();
        $detail->setField($field);
        $detail->setIssue('MISSING_REQUIRED_FIELD');
        $detail->setDescription(\sprintf('The field \'%s\' is invalid: %s', $field, $reason));

        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_REQUEST,
            $message,
            $parameters,
            new AgentErrorDetailCollection([$detail])
        );
    }

    public static function invalidJSONFormat(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_REQUEST,
            'Request body contains invalid JSON'
        );
    }

    public static function unauthorized(string $message, ?\Throwable $previous = null): self
    {
        return new self(
            Response::HTTP_UNAUTHORIZED,
            self::INVALID_REQUEST,
            $message,
            previous: $previous,
        );
    }

    public static function invalidCartId(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_CART_ID,
            'Cart ID format is invalid. Expected format: CART-[a-zA-Z0-9]{32}'
        );
    }

    public static function cartNotFound(string $token): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::CART_NOT_FOUND,
            'Cart with ID \'{{ token }}\' does not exist',
            ['token' => $token]
        );
    }

    public static function databaseConnectionFailure(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::INTERNAL_SERVER_ERROR,
            'A temporary system error occurred. Please try again later.'
        );
    }

    public static function externalServiceFailure(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::SERVICE_UNAVAILABLE,
            'The payment processor is currently unavailable. Please try again later.'
        );
    }

    public static function paymentProcessorUnavailable(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::PAYMENT_PROCESSOR_UNAVAILABLE,
            'Payment processing is temporarily unavailable'
        );
    }

    public static function paymentCaptureFailed(string $message): self
    {
        $detail = (new AgentErrorDetail());
        $detail->setField('payment_method');
        $detail->setIssue('CAPTURE_FAILED');
        $detail->setDescription($message);

        $details = new AgentErrorDetailCollection([$detail]);

        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::PAYMENT_CAPTURE_FAILED,
            'Unable to capture payment at this time',
            [],
            $details
        );
    }

    public static function inventorySystemError(): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::INVENTORY_SYSTEM_ERROR,
            'Unable to reserve inventory for checkout'
        );
    }

    public static function orderSystemError(?\Throwable $previous = null): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::ORDER_SYSTEM_ERROR,
            'Order could not be created due to system error',
            previous: $previous,
        );
    }
}
