<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PosException extends ShopwareHttpException
{
    private int $posStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

    public function __construct(
        string $name,
        string $message,
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
    ) {
        parent::__construct(
            'The error "{{ name }}" occurred with the following message: {{ message }}',
            ['name' => $name, 'message' => $message]
        );
        $this->posStatusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->posStatusCode;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__POS_EXCEPTION';
    }
}
