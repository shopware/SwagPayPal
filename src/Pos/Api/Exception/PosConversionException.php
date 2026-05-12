<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;

#[Package('checkout')]
class PosConversionException extends ShopwareHttpException
{
    public const SWAG_PAYPAL_POS_CONVERSION_EXCEPTION = 'SWAG_PAYPAL__POS_CONVERSION_EXCEPTION';

    public function __construct(
        string $entity,
        string $message,
    ) {
        parent::__construct(
            'The {{ entity }} could not be converted: {{ message }}',
            ['entity' => $entity, 'message' => $message]
        );
    }

    public function getErrorCode(): string
    {
        return self::SWAG_PAYPAL_POS_CONVERSION_EXCEPTION;
    }
}
