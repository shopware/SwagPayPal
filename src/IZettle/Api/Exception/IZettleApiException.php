<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Swag\PayPal\IZettle\Api\Error;

class IZettleApiException extends ShopwareHttpException
{
    /**
     * @var Error
     */
    private $apiError;

    public function __construct(Error $apiError)
    {
        $this->apiError = $apiError;
        parent::__construct($apiError->toString());
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__IZETTLE_API_EXCEPTION_' . $this->apiError->getErrorType();
    }

    public function getApiError(): Error
    {
        return $this->apiError;
    }
}
