<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Psr\Clock\ClockInterface;
use Swag\PayPal\Pos\Api\Service\ApiKeyDecoder;
use Swag\PayPal\Pos\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\Pos\Api\Service\Converter\OptionGroupConverter;
use Swag\PayPal\Pos\Api\Service\Converter\PresentationConverter;
use Swag\PayPal\Pos\Api\Service\Converter\PriceConverter;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Service\Converter\VariantConverter;
use Swag\PayPal\Pos\Api\Service\MediaConverter;
use Swag\PayPal\Pos\Api\Service\MetadataGenerator;
use Swag\PayPal\Pos\Api\Service\ProductConverter;
use Swag\PayPal\Pos\Client\PosClientFactory;
use Swag\PayPal\Pos\Client\TokenClientFactory;
use Swag\PayPal\Pos\Resource\ImageResource;
use Swag\PayPal\Pos\Resource\InventoryResource;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Resource\SubscriptionResource;
use Swag\PayPal\Pos\Resource\TokenResource;
use Swag\PayPal\Pos\Resource\UserResource;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(ApiKeyDecoder::class);

    $services->set(MetadataGenerator::class);

    $services->set(UuidConverter::class);

    $services->set(OptionGroupConverter::class);

    $services->set(PresentationConverter::class);

    $services->set(PriceConverter::class);

    $services->set(CategoryConverter::class)
        ->args([
            service(UuidConverter::class),
        ]);

    $services->set(VariantConverter::class)
        ->args([
            service(UuidConverter::class),
            service(PriceConverter::class),
            service(PresentationConverter::class),
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set(ProductConverter::class)
        ->args([
            service(UuidConverter::class),
            service(CategoryConverter::class),
            service(VariantConverter::class),
            service(OptionGroupConverter::class),
            service(PresentationConverter::class),
            service(MetadataGenerator::class),
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set(MediaConverter::class);

    $services->set(TokenClientFactory::class)
        ->args([
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set(PosClientFactory::class)
        ->args([
            service(TokenResource::class),
            service('Swag\PayPal\Pos\Run\Logger'),
        ]);

    $services->set(ImageResource::class)
        ->args([
            service(PosClientFactory::class),
        ]);

    $services->set(InventoryResource::class)
        ->args([
            service(PosClientFactory::class),
        ]);

    $services->set(ProductResource::class)
        ->args([
            service(PosClientFactory::class),
        ]);

    $services->set(SubscriptionResource::class)
        ->args([
            service(PosClientFactory::class),
        ]);

    $services->set(TokenResource::class)
        ->args([
            service('cache.object'),
            service(TokenClientFactory::class),
            service(ClockInterface::class),
        ]);

    $services->set(UserResource::class)
        ->args([
            service(PosClientFactory::class),
        ]);
};
