<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class PayPalApiException extends ShopwareHttpException
{
    public const ERROR_CODE_DUPLICATE_ORDER_NUMBER = 'DUPLICATE_TRANSACTION';
    public const ERROR_CODE_DUPLICATE_INVOICE_ID = 'DUPLICATE_INVOICE_ID';

    private ?int $payPalApiStatusCode;

    public function __construct(
        string $name,
        string $message,
        int $payPalApiStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR
    ) {
        parent::__construct(
            'The error "{{ name }}" occurred with the following message: {{ message }}',
            ['name' => $name, 'message' => $message]
        );
        $this->payPalApiStatusCode = $payPalApiStatusCode;
    }

    public function getStatusCode(): int
    {
        return $this->payPalApiStatusCode ?? parent::getStatusCode();
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__API_EXCEPTION';
    }
}
