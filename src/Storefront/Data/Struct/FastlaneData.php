<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class FastlaneData extends AbstractScriptData
{
    protected string $sdkClientToken;

    protected string $prepareCheckoutUrl;

    public function getSdkClientToken(): string
    {
        return $this->sdkClientToken;
    }

    public function setSdkClientToken(string $sdkClientToken): void
    {
        $this->sdkClientToken = $sdkClientToken;
    }

    public function getPrepareCheckoutUrl(): string
    {
        return $this->prepareCheckoutUrl;
    }

    public function setPrepareCheckoutUrl(string $prepareCheckoutUrl): void
    {
        $this->prepareCheckoutUrl = $prepareCheckoutUrl;
    }
}
