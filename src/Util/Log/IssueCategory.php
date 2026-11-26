<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Shopware\Core\Framework\Log\Package;

/**
 * Who has to act on a PayPal error, see `IssueClassifier`.
 *
 * @internal
 */
#[Package('checkout')]
enum IssueCategory: string
{
    /**
     * The merchant has to fix their PayPal account or resolve it with PayPal, until then PayPal rejects these requests.
     */
    case MERCHANT = 'MERCHANT';

    /**
     * The buyer can fix it, e.g. by correcting the address or approving the payment, so show them the issue.
     */
    case BUYER_RESOLVABLE = 'BUYER_RESOLVABLE';

    /**
     * Caused by the buyer, who can't fix it in this checkout, e.g. a declined card or a restricted PayPal account.
     * Another payment method may succeed.
     */
    case BUYER = 'BUYER';

    /**
     * The order or authorization expired, e.g. capturing an expired authorization.
     * Nobody can fix it and retrying doesn't help.
     */
    case UNRESOLVABLE = 'UNRESOLVABLE';

    /**
     * The integration sent a request PayPal can't process, e.g. amounts that don't add up, so a developer has to fix the code.
     */
    case LOGIC = 'LOGIC';
}
