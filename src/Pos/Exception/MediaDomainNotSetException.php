<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;

#[Package('checkout')]
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
        return 'SWAG_PAYPAL_POS__MEDIA_DOMAIN_NOT_SET';
    }
}
