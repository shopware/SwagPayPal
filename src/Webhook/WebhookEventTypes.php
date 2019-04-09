<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook;

/**
 * @url https://developer.paypal.com/docs/integration/direct/rest/webhooks/event-names/
 */
final class WebhookEventTypes
{
    public const ALL_EVENTS = '*';

    /* A billing plan is created. */
    public const BILLING_PLAN_CREATED = 'BILLING.PLAN.CREATED';
    /* A billing plan is updated. */
    public const BILLING_PLAN_UPDATE = 'BILLING.PLAN.CREATED';
    /* A billing subscription is canceled. */
    public const BILLING_SUBSCRIPTION_CANCELLED = 'BILLING.SUBSCRIPTION.CANCELLED';
    /* A billing subscription is created. */
    public const BILLING_SUBSCRIPTION_CREATED = 'BILLING_SUBSCRIPTION_CREATED';
    /* A billing subscription is re-activated. */
    public const BILLING_SUBSCRIPTION_REACTIVATED = 'BILLING.SUBSCRIPTION.RE-ACTIVATED';
    /* A billing subscription is suspended. */
    public const BILLING_SUBSCRIPTION_SUSPENDED = 'BILLING.SUBSCRIPTION.SUSPENDED';
    /* A billing subscription is updated. */
    public const BILLING_SUBSCRIPTION_UPDATED = 'BILLING.SUBSCRIPTION.UPDATED';

    /* A customer dispute is created. */
    public const CUSTOMER_DISPUTE_CREATED = 'CUSTOMER.DISPUTE.CREATED';
    /* A customer dispute is resolved. */
    public const CUSTOMER_DISPUTE_RESOLVED = 'CUSTOMER.DISPUTE.RESOLVED';
    /* A risk dispute is created. */
    public const RISK_DISPUTE_CREATED = 'RISK.DISPUTE.CREATED';

    /* A user's consent token is revoked. */
    public const IDENTITY_AUTHORIZATIONCONSENT_REVOKED = 'IDENTITY.AUTHORIZATION-CONSENT.REVOKED';
    /* An invoice is canceled. */
    public const INVOICING_INVOICE_CANCELLED = 'INVOICING.INVOICE.CANCELLED';
    /* An invoice is paid. */
    public const INVOICING_INVOICE_PAID = 'INVOICING.INVOICE.PAID';
    /* An invoice is refunded. */
    public const INVOICING_INVOICE_REFUNDED = 'INVOICING.INVOICE.REFUNDED';

    /* A payment authorization is created, approved, executed, or a future payment authorization is created. */
    public const PAYMENT_AUTHORIZATION_CREATED = 'PAYMENT.AUTHORIZATION.CREATED';
    /* A payment authorization is voided. */
    public const PAYMENT_AUTHORIZATION_VOIDED = 'PAYMENT.AUTHORIZATION.VOIDED';
    /* A payment capture is completed. */
    public const PAYMENT_CAPTURE_COMPLETED = 'PAYMENT.CAPTURE.COMPLETED';
    /* A payment capture is denied. */
    public const PAYMENT_CAPTURE_DENIED = 'PAYMENT.CAPTURE.DENIED';
    /* The state of a payment capture changes to pending. */
    public const PAYMENT_CAPTURE_PENDING = 'PAYMENT.CAPTURE.PENDING';
    /* Merchant refunds a payment capture. */
    public const PAYMENT_CAPTURE_REFUNDED = 'PAYMENT.CAPTURE.REFUNDED';
    /* PayPal reverses a payment capture. */
    public const PAYMENT_CAPTURE_REVERSED = 'PAYMENT.CAPTURE.REVERSED';

    /* A batch payout payment is denied. */
    public const PAYMENT_PAYOUTSBATCH_DENIED = 'PAYMENT.PAYOUTSBATCH.DENIED';
    /* The state of a batch payout payment changes to processing. */
    public const PAYMENT_PAYOUTSBATCH_PROCESSING = 'PAYMENT.PAYOUTSBATCH.PROCESSING';
    /* A batch payout payment successfully completes processing. */
    public const PAYMENT_PAYOUTSBATCH_SUCCESS = 'PAYMENT.PAYOUTSBATCH.SUCCESS';
    /* A payouts item was blocked. */
    public const PAYMENT_PAYOUTSITEM_BLOCKED = 'PAYMENT.PAYOUTS-ITEM.BLOCKED';
    /* A payouts item was cancelled. */
    public const PAYMENT_PAYOUTSITEM_CANCELED = 'PAYMENT.PAYOUTS-ITEM.CANCELED';
    /* A payouts item was denied. */
    public const PAYMENT_PAYOUTSITEM_DENIED = 'PAYMENT.PAYOUTS-ITEM.DENIED';
    /* A payouts item has failed. */
    public const PAYMENT_PAYOUTSITEM_FAILED = 'PAYMENT.PAYOUTS-ITEM.FAILED';
    /* A payouts item is held. */
    public const PAYMENT_PAYOUTSITEM_HELD = 'PAYMENT.PAYOUTS-ITEM.HELD';
    /* A payouts item was refunded. */
    public const PAYMENT_PAYOUTSITEM_REFUNDED = 'PAYMENT.PAYOUTS-ITEM.REFUNDED';
    /* A payouts item is returned. */
    public const PAYMENT_PAYOUTSITEM_RETURNED = 'PAYMENT.PAYOUTS-ITEM.RETURNED';
    /* A payouts item has succeeded. */
    public const PAYMENT_PAYOUTSITEM_SUCCEEDED = 'PAYMENT.PAYOUTS-ITEM.SUCCEEDED';
    /* A payouts item is unclaimed. */
    public const PAYMENT_PAYOUTSITEM_UNCLAIMED = 'PAYMENT.PAYOUTS-ITEM.UNCLAIMED';

    /* A sale is completed. */
    public const PAYMENT_SALE_COMPLETED = 'PAYMENT.SALE.COMPLETED';
    /* The state of a sale changes from pending to denied. */
    public const PAYMENT_SALE_DENIED = 'PAYMENT.SALE.DENIED';
    /* The state of a sale changes to pending. */
    public const PAYMENT_SALE_PENDING = 'PAYMENT.SALE.PENDING';
    /* Merchant refunds the sale. */
    public const PAYMENT_SALE_REFUNDED = 'PAYMENT.SALE.REFUNDED';
    /* PayPal reverses a sale. */
    public const PAYMENT_SALE_REVERSED = 'PAYMENT.SALE.REVERSED';

    /* A credit card was created. */
    public const VAULT_CREDITCARD_CREATED = 'VAULT.CREDIT-CARD.CREATED';
    /* A credit card was deleted. */
    public const VAULT_CREDITCARD_DELETED = 'VAULT.CREDIT-CARD.DELETED';
    /* A credit card was updated. */
    public const VAULT_CREDITCARD_UPDATED = 'VAULT.CREDIT-CARD.UPDATED';
}
