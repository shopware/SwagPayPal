<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;

class UuidConverterTest extends TestCase
{
    public function dataProviderUuidConversionToV1(): array
    {
        return [
            ['1ce0868f406d47d98cfe4b281e62f099', '1ce0868f-406d-17d9-8cfe-4b281e62f099'],
            ['notAUuid', ''],
        ];
    }

    /**
     * @dataProvider dataProviderUuidConversionToV1
     */
    public function testConvertToV1(string $originalUuid, string $expectedUuid): void
    {
        if ($expectedUuid === '') {
            $this->expectException(InvalidUuidException::class);
        }
        static::assertSame($expectedUuid, $this->createUuidConverter()->convertUuidToV1($originalUuid));
    }

    public function dataProviderUuidConversionToV4(): array
    {
        return [
            ['1ce0868f-406d-17d9-8cfe-4b281e62f099', '1ce0868f406d47d98cfe4b281e62f099'],
            ['notAUuid', ''],
        ];
    }

    /**
     * @dataProvider dataProviderUuidConversionToV4
     */
    public function testConvertToV4(string $originalUuid, string $expectedUuid): void
    {
        if ($expectedUuid === '') {
            $this->expectException(InvalidUuidException::class);
        }
        static::assertEquals($expectedUuid, $this->createUuidConverter()->convertUuidToV4($originalUuid));
    }

    public function dataProviderUuidIncrementation(): array
    {
        return [
            ['1ce0868f406d47d98cfe4b281e62f099', '1ce0868f406d47d98cfe4b281e62f09a'],
            ['1ce0868f406d47d98cfe4b281e62f09f', '1ce0868f406d47d98cfe4b281e62f090'],
            ['notAUuid', ''],
        ];
    }

    /**
     * @dataProvider dataProviderUuidIncrementation
     */
    public function testIncrement(string $originalUuid, string $expectedUuid): void
    {
        if ($expectedUuid === '') {
            $this->expectException(InvalidUuidException::class);
        }
        static::assertEquals($expectedUuid, $this->createUuidConverter()->incrementUuid($originalUuid));
    }

    private function createUuidConverter(): UuidConverter
    {
        return new UuidConverter();
    }
}
