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
class OrderFailedException extends ShopwareHttpException
{
    /**
     * @param string|null $status the status PayPal answered the capture or authorization with
     */
    public function __construct(
        string $orderId,
        private readonly ?string $status = null,
    ) {
        parent::__construct(
            'Order "{{ orderId }}" failed',
            ['orderId' => $orderId, ...($status !== null ? ['status' => $status] : [])]
        );
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__ORDER_FAILED';
    }
}
