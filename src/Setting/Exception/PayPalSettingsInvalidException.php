<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PayPalSettingsInvalidException extends ShopwareHttpException
{
    public function __construct(string $missingSetting)
    {
        parent::__construct(
            'Required setting "{{ missingSetting }}" is missing or invalid',
            ['missingSetting' => $missingSetting]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__REQUIRED_SETTING_INVALID';
    }
}
