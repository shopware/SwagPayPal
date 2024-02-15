<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Shopware\Core\Framework\Log\Package;

/**
 * @url https://developer.paypal.com/docs/api-basics/notifications/webhooks/event-names/
 */
#[Package('checkout')]
final class WebhookEventTypes
{
    public const ALL_EVENTS = '*';
    /* A payment authorization is created, approved, executed, or a future payment authorization is created. */
    public const PAYMENT_AUTHORIZATION_CREATED = 'PAYMENT.AUTHORIZATION.CREATED';
    /* A payment authorization is voided either due to authorization reaching it’s 30 day validity period or authorization was manually voided using the Void Authorized Payment API. */
    public const PAYMENT_AUTHORIZATION_VOIDED = 'PAYMENT.AUTHORIZATION.VOIDED';
    /* A payment capture is declined. */
    public const PAYMENT_CAPTURE_DECLINED = 'PAYMENT.CAPTURE.DECLINED';
    /* A payment capture completes. */
    public const PAYMENT_CAPTURE_COMPLETED = 'PAYMENT.CAPTURE.COMPLETED';
    /* The state of a payment capture changes to pending. */
    public const PAYMENT_CAPTURE_PENDING = 'PAYMENT.CAPTURE.PENDING';
    /* A merchant refunds a payment capture. */
    public const PAYMENT_CAPTURE_REFUNDED = 'PAYMENT.CAPTURE.REFUNDED';
    /* PayPal reverses a payment capture. */
    public const PAYMENT_CAPTURE_REVERSED = 'PAYMENT.CAPTURE.REVERSED';
    /* A payment capture is denied. */
    public const PAYMENT_CAPTURE_DENIED = 'PAYMENT.CAPTURE.DENIED';
    /* A batch payout payment is denied. */
    public const PAYMENT_PAYOUTSBATCH_DENIED = 'PAYMENT.PAYOUTSBATCH.DENIED';
    /* The state of a batch payout payment changes to processing. */
    public const PAYMENT_PAYOUTSBATCH_PROCESSING = 'PAYMENT.PAYOUTSBATCH.PROCESSING';
    /* A batch payout payment completes successfully. */
    public const PAYMENT_PAYOUTSBATCH_SUCCESS = 'PAYMENT.PAYOUTSBATCH.SUCCESS';
    /* A payouts item is blocked. */
    public const PAYMENT_PAYOUTS_ITEM_BLOCKED = 'PAYMENT.PAYOUTS-ITEM.BLOCKED';
    /* A payouts item is canceled. */
    public const PAYMENT_PAYOUTS_ITEM_CANCELED = 'PAYMENT.PAYOUTS-ITEM.CANCELED';
    /* A payouts item is denied. */
    public const PAYMENT_PAYOUTS_ITEM_DENIED = 'PAYMENT.PAYOUTS-ITEM.DENIED';
    /* A payouts item fails. */
    public const PAYMENT_PAYOUTS_ITEM_FAILED = 'PAYMENT.PAYOUTS-ITEM.FAILED';
    /* A payouts item is held. */
    public const PAYMENT_PAYOUTS_ITEM_HELD = 'PAYMENT.PAYOUTS-ITEM.HELD';
    /* A payouts item is refunded. */
    public const PAYMENT_PAYOUTS_ITEM_REFUNDED = 'PAYMENT.PAYOUTS-ITEM.REFUNDED';
    /* A payouts item is returned. */
    public const PAYMENT_PAYOUTS_ITEM_RETURNED = 'PAYMENT.PAYOUTS-ITEM.RETURNED';
    /* A payouts item succeeds. */
    public const PAYMENT_PAYOUTS_ITEM_SUCCEEDED = 'PAYMENT.PAYOUTS-ITEM.SUCCEEDED';
    /* A payouts item is unclaimed. */
    public const PAYMENT_PAYOUTS_ITEM_UNCLAIMED = 'PAYMENT.PAYOUTS-ITEM.UNCLAIMED';
    /* A billing plan is created. */
    public const BILLING_PLAN_CREATED = 'BILLING.PLAN.CREATED';
    /* A billing plan is updated. */
    public const BILLING_PLAN_UPDATED = 'BILLING.PLAN.UPDATED';
    /* A billing agreement is canceled. */
    public const BILLING_SUBSCRIPTION_CANCELLED = 'BILLING.SUBSCRIPTION.CANCELLED';
    /* A billing agreement is created. */
    public const BILLING_SUBSCRIPTION_CREATED = 'BILLING.SUBSCRIPTION.CREATED';
    /* A billing agreement is re-activated. */
    public const BILLING_SUBSCRIPTION_RE_ACTIVATED = 'BILLING.SUBSCRIPTION.RE-ACTIVATED';
    /* A billing agreement is suspended. */
    public const BILLING_SUBSCRIPTION_SUSPENDED = 'BILLING.SUBSCRIPTION.SUSPENDED';
    /* A billing agreement is updated. */
    public const BILLING_SUBSCRIPTION_UPDATED = 'BILLING.SUBSCRIPTION.UPDATED';
    /* A user's consent token is revoked. */
    public const IDENTITY_AUTHORIZATION_CONSENT_REVOKED = 'IDENTITY.AUTHORIZATION-CONSENT.REVOKED';
    /* Checkout payment is created and approved by buyer. */
    public const PAYMENTS_PAYMENT_CREATED = 'PAYMENTS.PAYMENT.CREATED';
    /* A buyer approved a checkout order */
    public const CHECKOUT_ORDER_APPROVED = 'CHECKOUT.ORDER.APPROVED';
    /* Express checkout payment is created and approved by buyer. */
    public const CHECKOUT_CHECKOUT_BUYER_APPROVED = 'CHECKOUT.CHECKOUT.BUYER-APPROVED';
    /* A dispute is created. */
    public const CUSTOMER_DISPUTE_CREATED = 'CUSTOMER.DISPUTE.CREATED';
    /* A dispute is resolved. */
    public const CUSTOMER_DISPUTE_RESOLVED = 'CUSTOMER.DISPUTE.RESOLVED';
    /* A dispute is updated. */
    public const CUSTOMER_DISPUTE_UPDATED = 'CUSTOMER.DISPUTE.UPDATED';
    /* A risk dispute is created. */
    public const RISK_DISPUTE_CREATED = 'RISK.DISPUTE.CREATED';
    /* A merchant or customer cancels an invoice. */
    public const INVOICING_INVOICE_CANCELLED = 'INVOICING.INVOICE.CANCELLED';
    /* An invoice is created. */
    public const INVOICING_INVOICE_CREATED = 'INVOICING.INVOICE.CREATED';
    /* An invoice is paid, partially paid, or payment is made and is pending. */
    public const INVOICING_INVOICE_PAID = 'INVOICING.INVOICE.PAID';
    /* An invoice is refunded or partially refunded. */
    public const INVOICING_INVOICE_REFUNDED = 'INVOICING.INVOICE.REFUNDED';
    /* An invoice is scheduled. */
    public const INVOICING_INVOICE_SCHEDULED = 'INVOICING.INVOICE.SCHEDULED';
    /* An invoice is updated. */
    public const INVOICING_INVOICE_UPDATED = 'INVOICING.INVOICE.UPDATED';
    /* A Buyer's order has been completed. */
    public const CHECKOUT_ORDER_COMPLETED = 'CHECKOUT.ORDER.COMPLETED';
    /* A Buyer's order is being processed. */
    public const CHECKOUT_ORDER_PROCESSED = 'CHECKOUT.ORDER.PROCESSED';
    /* A limitation is added for a partner's managed account. */
    public const CUSTOMER_ACCOUNT_LIMITATION_ADDED = 'CUSTOMER.ACCOUNT-LIMITATION.ADDED';
    /* A limitation is escalated for a partner's managed account. */
    public const CUSTOMER_ACCOUNT_LIMITATION_ESCALATED = 'CUSTOMER.ACCOUNT-LIMITATION.ESCALATED';
    /* A limitation is lifted for a partner's managed account. */
    public const CUSTOMER_ACCOUNT_LIMITATION_LIFTED = 'CUSTOMER.ACCOUNT-LIMITATION.LIFTED';
    /* A limitation is updated for a partner's managed account. */
    public const CUSTOMER_ACCOUNT_LIMITATION_UPDATED = 'CUSTOMER.ACCOUNT-LIMITATION.UPDATED';
    /* PayPal must enable the merchant's account as PPCP for this webhook to work. */
    public const CUSTOMER_MERCHANT_INTEGRATION_CAPABILITY_UPDATED = 'CUSTOMER.MERCHANT-INTEGRATION.CAPABILITY-UPDATED';
    /* The products available to the merchant have changed. */
    public const CUSTOMER_MERCHANT_INTEGRATION_PRODUCT_SUBSCRIPTION_UPDATED = 'CUSTOMER.MERCHANT-INTEGRATION.PRODUCT-SUBSCRIPTION-UPDATED';
    /* Merchant onboards again to a partner. */
    public const CUSTOMER_MERCHANT_INTEGRATION_SELLER_ALREADY_INTEGRATED = 'CUSTOMER.MERCHANT-INTEGRATION.SELLER-ALREADY-INTEGRATED';
    /* PayPal creates a merchant account from the partner's onboarding link. */
    public const CUSTOMER_MERCHANT_INTEGRATION_SELLER_ONBOARDING_INITIATED = 'CUSTOMER.MERCHANT-INTEGRATION.SELLER-ONBOARDING-INITIATED';
    /* Merchant grants consents to a partner. */
    public const CUSTOMER_MERCHANT_INTEGRATION_SELLER_CONSENT_GRANTED = 'CUSTOMER.MERCHANT-INTEGRATION.SELLER-CONSENT-GRANTED';
    /* Merchant confirms the email and consents are granted. */
    public const CUSTOMER_MERCHANT_INTEGRATION_SELLER_EMAIL_CONFIRMED = 'CUSTOMER.MERCHANT-INTEGRATION.SELLER-EMAIL-CONFIRMED';
    /* Merchant completes setup. */
    public const MERCHANT_ONBOARDING_COMPLETED = 'MERCHANT.ONBOARDING.COMPLETED';
    /* The consents for a merchant account setup are revoked or an account is closed. */
    public const MERCHANT_PARTNER_CONSENT_REVOKED = 'MERCHANT.PARTNER-CONSENT.REVOKED';
    /* Funds are disbursed to the seller and partner. */
    public const PAYMENT_REFERENCED_PAYOUT_ITEM_COMPLETED = 'PAYMENT.REFERENCED-PAYOUT-ITEM.COMPLETED';
    /* Attempt to disburse funds fails. */
    public const PAYMENT_REFERENCED_PAYOUT_ITEM_FAILED = 'PAYMENT.REFERENCED-PAYOUT-ITEM.FAILED';
    /* Managed account has been created. */
    public const CUSTOMER_MANAGED_ACCOUNT_ACCOUNT_CREATED = 'CUSTOMER.MANAGED-ACCOUNT.ACCOUNT-CREATED';
    /* Managed account creation failed. */
    public const CUSTOMER_MANAGED_ACCOUNT_CREATION_FAILED = 'CUSTOMER.MANAGED-ACCOUNT.CREATION-FAILED';
    /* Managed account has been updated. */
    public const CUSTOMER_MANAGED_ACCOUNT_ACCOUNT_UPDATED = 'CUSTOMER.MANAGED-ACCOUNT.ACCOUNT-UPDATED';
    /* Capabilities and/or process status has been changed on a managed account. */
    public const CUSTOMER_MANAGED_ACCOUNT_ACCOUNT_STATUS_CHANGED = 'CUSTOMER.MANAGED-ACCOUNT.ACCOUNT-STATUS-CHANGED';
    /* Managed account has been risk assessed or the risk assessment has been changed. */
    public const CUSTOMER_MANAGED_ACCOUNT_RISK_ASSESSED = 'CUSTOMER.MANAGED-ACCOUNT.RISK-ASSESSED';
    /* Negative balance debit has been notified on a managed account. */
    public const CUSTOMER_MANAGED_ACCOUNT_NEGATIVE_BALANCE_NOTIFIED = 'CUSTOMER.MANAGED-ACCOUNT.NEGATIVE-BALANCE-NOTIFIED';
    /* Negative balance debit has been initiated on a managed account. */
    public const CUSTOMER_MANAGED_ACCOUNT_NEGATIVE_BALANCE_DEBIT_INITIATED = 'CUSTOMER.MANAGED-ACCOUNT.NEGATIVE-BALANCE-DEBIT-INITIATED';
    /* A problem occurred after the buyer approved the order but before you captured the payment. Refer to Handle uncaptured payments for what to do when this event occurs. */
    public const CHECKOUT_PAYMENT_APPROVAL_REVERSED = 'CHECKOUT.PAYMENT-APPROVAL.REVERSED';
    /* A payment order is canceled. */
    public const PAYMENT_ORDER_CANCELLED = 'PAYMENT.ORDER.CANCELLED';
    /* A payment order is created. */
    public const PAYMENT_ORDER_CREATED = 'PAYMENT.ORDER.CREATED';
    /* A sale completes. */
    public const PAYMENT_SALE_COMPLETED = 'PAYMENT.SALE.COMPLETED';
    /* The state of a sale changes from pending to denied. */
    public const PAYMENT_SALE_DENIED = 'PAYMENT.SALE.DENIED';
    /* The state of a sale changes to pending. */
    public const PAYMENT_SALE_PENDING = 'PAYMENT.SALE.PENDING';
    /* A merchant refunds a sale. */
    public const PAYMENT_SALE_REFUNDED = 'PAYMENT.SALE.REFUNDED';
    /* PayPal reverses a sale. */
    public const PAYMENT_SALE_REVERSED = 'PAYMENT.SALE.REVERSED';
    /* A product is created. */
    public const CATALOG_PRODUCT_CREATED = 'CATALOG.PRODUCT.CREATED';
    /* A product is updated. */
    public const CATALOG_PRODUCT_UPDATED = 'CATALOG.PRODUCT.UPDATED';
    /* A billing plan is activated. */
    public const BILLING_PLAN_ACTIVATED = 'BILLING.PLAN.ACTIVATED';
    /* A price change for the plan is activated. */
    public const BILLING_PLAN_PRICING_CHANGE_ACTIVATED = 'BILLING.PLAN.PRICING-CHANGE.ACTIVATED';
    /* A billing plan is deactivated. */
    public const BILLING_PLAN_DEACTIVATED = 'BILLING.PLAN.DEACTIVATED';
    /* A subscription is activated. */
    public const BILLING_SUBSCRIPTION_ACTIVATED = 'BILLING.SUBSCRIPTION.ACTIVATED';
    /* A subscription expires. */
    public const BILLING_SUBSCRIPTION_EXPIRED = 'BILLING.SUBSCRIPTION.EXPIRED';
    /* Payment failed on subscription. */
    public const BILLING_SUBSCRIPTION_PAYMENT_FAILED = 'BILLING.SUBSCRIPTION.PAYMENT.FAILED';
    /* A credit card is created. */
    public const VAULT_CREDIT_CARD_CREATED = 'VAULT.CREDIT-CARD.CREATED';
    /* A credit card is deleted. */
    public const VAULT_CREDIT_CARD_DELETED = 'VAULT.CREDIT-CARD.DELETED';
    /* A credit card is updated. */
    public const VAULT_CREDIT_CARD_UPDATED = 'VAULT.CREDIT-CARD.UPDATED';
    /* A payment method token is created. */
    public const VAULT_PAYMENT_TOKEN_CREATED = 'VAULT.PAYMENT-TOKEN.CREATED';
    /* A payment method token is deleted. */
    public const VAULT_PAYMENT_TOKEN_DELETED = 'VAULT.PAYMENT-TOKEN.DELETED';
}
