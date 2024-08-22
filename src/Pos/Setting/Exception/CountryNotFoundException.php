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
class CountryNotFoundException extends ShopwareHttpException
{
    public function __construct(string $countryCode)
    {
        parent::__construct(
            'Country entity with code "{{ countryCode }}" not found',
            ['countryCode' => $countryCode]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_POS__COUNTRY_NOT_FOUND';
    }
}
