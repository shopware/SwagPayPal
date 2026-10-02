<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Log\Package;

/**
 * The PayPal web SDK v6 has to be activated by the merchant in their PayPal account.
 * Whether that happened is expressed by the scopes granted to the client token of the merchant.
 */
#[Package('checkout')]
interface SdkV6EligibilityServiceInterface
{
    public const SCOPE_CLIENT_PAYMENTS_ELIGIBILITY = 'https://uri.paypal.com/services/payments/client-payments-eligibility';

    /**
     * @return bool|null null if the eligibility could not be determined, e.g. because PayPal could not be reached
     */
    public function isEligible(?string $salesChannelId = null): ?bool;
}
