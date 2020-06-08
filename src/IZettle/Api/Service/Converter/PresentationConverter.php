<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Service\Converter;

use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Swag\PayPal\IZettle\Api\Product\Presentation;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;

class PresentationConverter
{
    public function convert(?ProductMediaEntity $cover, ProductContext $productContext): ?Presentation
    {
        if ($cover === null) {
            return null;
        }

        $media = $cover->getMedia();
        if ($media === null) {
            return null;
        }

        $url = $productContext->checkForMediaUrl($media);
        if ($url === null) {
            return null;
        }

        $presentation = new Presentation();
        $presentation->setImageUrl($url);

        return $presentation;
    }
}
