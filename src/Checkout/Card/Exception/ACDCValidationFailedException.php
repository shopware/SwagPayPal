<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Card\Exception;

use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class ACDCValidationFailedException extends AsyncPaymentFinalizeException
{
    public function __construct(
        string $orderTransactionId,
        ?string $message = null
    ) {
        parent::__construct($orderTransactionId, $message ?? 'Credit card validation failed, 3D secure was not validated.');
    }
}
