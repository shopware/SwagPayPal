<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use Shopware\Core\Framework\Log\Package;

/**
 * Billing address for merchant business purposes, obtained from customer's PayPal profile. Similar to shipping addresses, billing addresses can be retrieved from customer's default address information stored in their PayPal account.
 *
 * When Billing Address is Available:
 *
 * Customer has a default billing address in their PayPal profile
 * PayPal Credit and Buy Now Pay Later transactions
 * Guest checkout with credit/debit cards
 * User explicitly consents to address sharing
 * Required for tax compliance and regulatory reporting
 *
 * Primary Use Cases:
 *
 * Tax calculation: Sales tax/VAT rates determined by billing jurisdiction
 * Export compliance: Product restrictions based on customer's billing country
 * Financial reporting: Accounting systems requiring customer billing location
 * Address verification: Comparing billing vs shipping addresses for fraud prevention
 *
 * Secondary Use Cases:
 *
 * Business intelligence: Customer demographics and market analysis
 * B2B invoicing: Legal invoices requiring customer billing details
 * Compliance reporting: Regulatory requirements based on customer location
 *
 * Note: Payment verification (AVS) and chargeback protection are handled by PayPal internally.
 *
 * Implementation Notes:
 *
 * Billing address is typically available from customer profile data
 * Can be populated during cart creation if customer provides it
 * Falls back to shipping address when billing address is not specified
 * Merchants should handle graceful fallback scenarios
 *
 * @experimental
 */
#[Package('checkout')]
class BillingAddress extends Address
{
}
