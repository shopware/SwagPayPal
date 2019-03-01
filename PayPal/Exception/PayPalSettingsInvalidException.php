<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class PayPalSettingsInvalidException extends ShopwareHttpException
{
    protected $code = 'SWAG-PAYPAL-REQUIRED-SETTING-INVALID';

    public function __construct(string $missingSetting, $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('Required setting "%s" is missing or invalid', $missingSetting);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
