<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PayPalApiException extends ShopwareHttpException
{
    public const ERROR_CODE_DUPLICATE_ORDER_NUMBER = 'DUPLICATE_TRANSACTION';
    public const ERROR_CODE_DUPLICATE_INVOICE_ID = 'DUPLICATE_INVOICE_ID';

    private int $payPalApiStatusCode;

    private ?string $issue;

    public function __construct(
        string $name,
        string $message,
        int $payPalApiStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?string $issue = null
    ) {
        $this->payPalApiStatusCode = $payPalApiStatusCode;
        $this->issue = $issue;
        parent::__construct(
            'The error "{{ name }}" occurred with the following message: {{ message }}',
            ['name' => $name, 'message' => $message]
        );
    }

    public function getStatusCode(): int
    {
        return $this->payPalApiStatusCode;
    }

    public function getIssue(): ?string
    {
        return $this->issue;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__API_EXCEPTION';
    }
}
