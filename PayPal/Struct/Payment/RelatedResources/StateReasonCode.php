<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\RelatedResources;

class StateReasonCode
{
    public const CHARGEBACK = 'CHARGEBACK';
    public const GUARANTEE = 'GUARANTEE';
    public const BUYER_COMPLAINT = 'BUYER_COMPLAINT';
    public const REFUND = 'REFUND';
    public const UNCONFIRMED_SHIPPING_ADDRESS = 'UNCONFIRMED_SHIPPING_ADDRESS';
    public const ECHECK = 'ECHECK';
    public const INTERNATIONAL_WITHDRAWAL = 'INTERNATIONAL_WITHDRAWAL';
    public const RECEIVING_PREFERENCE_MANDATES_MANUAL_ACTION = 'RECEIVING_PREFERENCE_MANDATES_MANUAL_ACTION';
    public const PAYMENT_REVIEW = 'PAYMENT_REVIEW';
    public const REGULATORY_REVIEW = 'REGULATORY_REVIEW';
    public const UNILATERAL = 'UNILATERAL';
    public const VERIFICATION_REQUIRED = 'VERIFICATION_REQUIRED';
    public const TRANSACTION_APPROVED_AWAITING_FUNDING = 'TRANSACTION_APPROVED_AWAITING_FUNDING';
}
