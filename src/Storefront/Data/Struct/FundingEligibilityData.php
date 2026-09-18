<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class FundingEligibilityData extends AbstractScriptData
{
    /**
     * @var string[]
     */
    protected array $filteredPaymentMethods;

    protected string $methodEligibilityUrl;

    /**
     * @deprecated tag:v11.0.0 - Will be removed, SEPA eligibility will be checked via SDK v6
     */
    protected bool $sepaActive = false;

    /**
     * @return string[]
     */
    public function getFilteredPaymentMethods(): array
    {
        return $this->filteredPaymentMethods;
    }

    /**
     * @param string[] $filteredPaymentMethods
     */
    public function setFilteredPaymentMethods(array $filteredPaymentMethods): void
    {
        $this->filteredPaymentMethods = $filteredPaymentMethods;
    }

    public function getMethodEligibilityUrl(): string
    {
        return $this->methodEligibilityUrl;
    }

    public function setMethodEligibilityUrl(string $methodEligibilityUrl): void
    {
        $this->methodEligibilityUrl = $methodEligibilityUrl;
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed, SEPA eligibility will be checked via SDK v6
     */
    public function isSepaActive(): bool
    {
        return $this->sepaActive;
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed, SEPA eligibility will be checked via SDK v6
     */
    public function setSepaActive(bool $sepaActive): void
    {
        $this->sepaActive = $sepaActive;
    }
}
