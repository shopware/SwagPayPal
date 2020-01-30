<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class PayPalApiException extends ShopwareHttpException
{
    public function __construct(string $name, string $message)
    {
        parent::__construct(
            'The error "{{ name }}" occurred with the following message: {{ message }}',
            ['name' => $name, 'message' => $message]
        );
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__API_EXCEPTION';
    }
}
