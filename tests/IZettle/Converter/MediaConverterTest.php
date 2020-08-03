<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\Api\Service\MediaConverter;
use Swag\PayPal\IZettle\Exception\InvalidMediaTypeException;

class MediaConverterTest extends TestCase
{
    private const MEDIA_URL = 'https://via.placeholder.com/500x500';
    private const MEDIA_RELATIVE_URL = '500x500';
    private const DOMAIN_URL = 'https://via.placeholder.com';
    private const LOOKUP_KEY = 'existingLookupKey';

    public function testConvert(): void
    {
        $shopwareMedia = $this->getMedia();

        $image = $this->createMediaConverter()->convert(self::DOMAIN_URL, $shopwareMedia);

        static::assertSame(self::MEDIA_URL, $image->getImageUrl());
        static::assertSame('JPEG', $image->getImageFormat());
        static::assertNull($image->getImageLookupKey());
    }

    public function testConvertInvalidFormat(): void
    {
        $shopwareMedia = $this->getMedia();
        $shopwareMedia->setMimeType('video/mp4');

        $this->expectException(InvalidMediaTypeException::class);
        $this->createMediaConverter()->convert(self::DOMAIN_URL, $shopwareMedia);
    }

    public function testConvertExisting(): void
    {
        $shopwareMedia = $this->getMedia();

        $image = $this->createMediaConverter()->convert(self::DOMAIN_URL, $shopwareMedia, self::LOOKUP_KEY);

        static::assertSame(self::MEDIA_URL, $image->getImageUrl());
        static::assertSame('JPEG', $image->getImageFormat());
        static::assertSame(self::LOOKUP_KEY, $image->getImageLookupKey());
    }

    private function createMediaConverter(): MediaConverter
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('getRelativeMediaUrl')->willReturn(self::MEDIA_RELATIVE_URL);

        return new MediaConverter($urlGenerator);
    }

    private function getMedia(): MediaEntity
    {
        $media = new MediaEntity();
        $media->setId(Uuid::randomHex());
        $media->setMimeType('image/jpeg');

        return $media;
    }
}
