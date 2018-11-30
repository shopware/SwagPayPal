<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Setting\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PayPalSettingsNotFoundException extends ShopwareHttpException
{
    protected $code = 'SWAG-PAYPAL-SETTINGS-NOT-FOUND';

    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct('PayPal settings not found', $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
