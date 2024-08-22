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
class InvalidApiKeyException extends ShopwareHttpException
{
    public function __construct(string $part)
    {
        parent::__construct(
            'The given API key is invalid. The {{ part }} is incorrect.',
            ['part' => $part]
        );
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_POS__INVALID_API_KEY';
    }
}
