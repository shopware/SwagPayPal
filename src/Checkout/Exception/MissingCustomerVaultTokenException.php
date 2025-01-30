<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class MissingCustomerVaultTokenException extends ShopwareHttpException
{
    public function __construct(string $customerId)
    {
        parent::__construct(
            'Missing vault token for customer "{{ customerId }}"',
            ['customerId' => $customerId]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__MISSING_CUSTOMER_VAULT_TOKEN';
    }
}
