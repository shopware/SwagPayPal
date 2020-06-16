<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class NoDomainAssignedException extends ShopwareHttpException
{
    public function __construct(string $id)
    {
        parent::__construct(
            'Sales Channel "{{ id }}" has no domain assigned.',
            ['id' => $id]
        );
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_IZETTLE__NO_DOMAIN_ASSIGNED';
    }
}
