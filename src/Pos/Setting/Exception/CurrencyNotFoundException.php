<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class CurrencyNotFoundException extends ShopwareHttpException
{
    public function __construct(string $currencyCode)
    {
        parent::__construct(
            'Currency entity with code "{{ currencyCode }}" not found',
            ['currencyCode' => $currencyCode]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_POS__CURRENCY_NOT_FOUND';
    }
}
