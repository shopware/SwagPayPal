<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class InvalidMediaTypeException extends ShopwareHttpException
{
    public function __construct(string $mimeType)
    {
        parent::__construct(
            'The media with the MIME type "{{ mimeType }}" is not accepted by iZettle.',
            ['mimeType' => $mimeType]
        );
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_IZETTLE__INVALID_MEDIA_TYPE';
    }
}
