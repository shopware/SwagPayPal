<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Exception;

use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class OrderBuildException extends PaymentException
{
    public const ERROR_CODE_ORDER_BUILDER_DOES_NOT_SUPPORT_CART = 'SWAG_PAYPAL__ORDER_BUILDER_DOES_NOT_SUPPORT_CART';

    public static function cartNotSupported(string $orderBuilderName): PaymentException
    {
        return new self(
            Response::HTTP_NOT_IMPLEMENTED,
            self::ERROR_CODE_ORDER_BUILDER_DOES_NOT_SUPPORT_CART,
            'The order builder "{{ orderBuilderName }}" does not support building orders before the cart is converted to an order.',
            ['orderBuilderName' => $orderBuilderName]
        );
    }

    /**
     * @deprecated tag:v10.0.0 - will be removed with Shopware 6.7 compatible version
     */
    public function setOrderTransactionId(string $orderTransactionId): void
    {
        $this->parameters['orderTransactionId'] = $orderTransactionId;
    }
}
