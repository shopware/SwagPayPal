<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Setting\Struct;

use Shopware\Core\Framework\Struct\Struct;

class AdditionalInformation extends Struct
{
    /**
     * @var string
     */
    protected $currencyId;

    public function setCurrencyId(string $currencyId): void
    {
        $this->currencyId = $currencyId;
    }
}
