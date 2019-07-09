<?php declare(strict_types=1);

namespace Swag\PayPal\Payment\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CurrencyNotFoundException extends ShopwareHttpException
{
    public function __construct(string $currencyId)
    {
        parent::__construct(
            'Currency entity with id "{{ currencyId }}" not found',
            ['currencyId' => $currencyId]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__CURRENCY_NOT_FOUND';
    }
}
