<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Dispute\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class NotAuthorizedException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct('Not authorized');
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_UNAUTHORIZED;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__NOT_AUTHORIZED';
    }
}
