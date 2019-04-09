<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class PayPalSettingsNotFoundException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct('PayPal settings not found');
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__SETTINGS_NOT_FOUND';
    }
}
