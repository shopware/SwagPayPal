<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Service;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Product\Metadata;
use Swag\PayPal\Pos\Api\Product\Metadata\Source;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class MetadataGenerator
{
    public function generate(): Metadata
    {
        $metadata = new Metadata();
        $metadata->setInPos(true);

        $source = new Source();
        $source->setExternal(true);
        $source->setName(SwagPayPal::POS_PARTNER_IDENTIFIER);
        $metadata->setSource($source);

        return $metadata;
    }
}
