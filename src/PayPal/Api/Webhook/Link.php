<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Webhook;

use Swag\PayPal\PayPal\Api\Common\Link as CommonLink;

class Link extends CommonLink
{
    /**
     * @var string
     */
    private $encType;

    protected function setEncType(string $encType): void
    {
        $this->encType = $encType;
    }
}
