<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Card;

use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V2\Api\Order;

#[Package('checkout')]
interface CardValidatorInterface
{
    public const LIABILITY_SHIFT_POSSIBLE = 'POSSIBLE';

    public const LIABILITY_SHIFT_YES = 'YES';
    public const LIABILITY_SHIFT_NO = 'NO';
    public const LIABILITY_SHIFT_UNKNOWN = 'UNKNOWN';

    public const ENROLLMENT_STATUS_READY = 'Y';
    public const ENROLLMENT_STATUS_NOT_READY = 'N';
    public const ENROLLMENT_STATUS_UNAVAILABLE = 'U';
    public const ENROLLMENT_STATUS_BYPASSED = 'B';

    public const AUTHENTICATION_STATUS_SUCCESSFUL = 'Y';
    public const AUTHENTICATION_STATUS_FAILED = 'N';
    public const AUTHENTICATION_STATUS_REJECTED = 'R';
    public const AUTHENTICATION_STATUS_ATTEMPTED = 'A';
    public const AUTHENTICATION_STATUS_UNABLE_TO_COMPLETE = 'U';
    public const AUTHENTICATION_STATUS_CHALLENGE_REQUIRED = 'C';
    public const AUTHENTICATION_STATUS_INFORMATION_ONLY = 'I';
    public const AUTHENTICATION_STATUS_DECOUPLED = 'D';

    public function validate(Order $order, SyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext): bool;
}
