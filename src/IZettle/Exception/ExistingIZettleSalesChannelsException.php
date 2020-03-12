<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class ExistingIZettleSalesChannelsException extends ShopwareHttpException
{
    /**
     * @param string[] $names
     */
    public function __construct(int $amount, array $names)
    {
        $quantityWords = [
            $amount === 1 ? 'is' : 'are',
            $amount === 1 ? 'channel' : 'channels',
        ];

        parent::__construct(sprintf(
            'There %s still %d IZettle sales %s left. [%s]',
            $quantityWords[0],
            $amount,
            $quantityWords[1],
            implode(', ', $names)
        ));
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_IZETTLE__EXISTING_SALES_CHANNELS';
    }
}
