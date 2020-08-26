<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class MediaDomainNotSetException extends ShopwareHttpException
{
    public function __construct(string $salesChannelId)
    {
        parent::__construct(
            'The media domain of the sales channel {{ salesChannelId }} is not set.',
            ['salesChannelId' => $salesChannelId]
        );
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_IZETTLE__MEDIA_DOMAIN_NOT_SET';
    }
}
