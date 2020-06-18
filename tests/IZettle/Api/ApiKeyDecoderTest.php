<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Api;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\IZettle\Api\Exception\InvalidApiKeyException;
use Swag\PayPal\IZettle\Api\Service\ApiKeyDecoder;

class ApiKeyDecoderTest extends TestCase
{
    private const EXAMPLE_API_KEY = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IjAifQ.eyJpc3MiOiJpWmV0dGxlIiwiYXVkIjoiQVBJIiwiZXhwIjoyNTM0Nzg1NzMxLCJzdWIiOiJhZmFiYzExOC00OWI4LTExZWEtODBlNi0wOWVlZWU0MGIxNjEiLCJpYXQiOjE1ODgwNzc5NTUsInJlbmV3ZWQiOmZhbHNlLCJzY29wZSI6WyJXUklURTpQUk9EVUNUIiwiUkVBRDpQUk9EVUNUIiwiUkVBRDpVU0VSSU5GTyIsIlJFQUQ6RklOQU5DRSIsIlJFQUQ6UFVSQ0hBU0UiXSwidXNlciI6eyJ1c2VyVHlwZSI6IlVTRVIiLCJ1dWlkIjoiYWZhYmMxMTgtNDliOC0xMWVhLTgwZTYtMDllZWVlNDBiMTYxIiwib3JnVXVpZCI6ImFmYWFlMmZjLTQ5YjgtMTFlYS05OGI5LWFkNzUwMjcxMTIxNCIsInVzZXJSb2xlIjoiT1dORVIifSwidHlwZSI6InVzZXItYXNzZXJ0aW9uIiwiY2xpZW50X2lkIjoiNTkxNjRjZTAtYjE2Ni0xMWVhLTgwOTEtNWU2NmNlNThiM2ZhIn0.VkXyBzrEOeUM1K9w5NhYqumcfShm738LMJG3JMW3FENrM90eGMZxfYaoY3jFYws2MkjktGShsf_8LQ4ZqDzeetREWQ8A0DPN2_0GXqbf-jZVGFwCR_Oxy2FBrBcIdjmeQMq_cX4siFd0aAxAcraA5IJIng81Jx1SEu4aA72apGylqW1l3oZ1YUXNgUd9zOj5OKPK_uhxMSLyJ8MD_fyXQH8BDUxJ8Y4dByJYDkXOzHz1C-uWEVrhIJ0OGVmEnh1Cxq2gtKyjQcz3rMZg2VN52GY_Yx2AcWlnjiwxf0nlMVSHegKyGfnVoyXIw-H4T2mA_R0NmixxT7teJ8NsPTd9NQ';
    private const EXAMPLE_CLIENT_ID = '59164ce0-b166-11ea-8091-5e66ce58b3fa';

    public function testClientId(): void
    {
        $decoder = new ApiKeyDecoder();

        $decoded = $decoder->decode(self::EXAMPLE_API_KEY);

        static::assertEquals(self::EXAMPLE_CLIENT_ID, $decoded->getPayload()->getClientId());
    }

    public function dataProviderMalformedSegments(): array
    {
        return [
            [0, 'header'],
            [1, 'payload'],
            [2, 'signature'],
            [3, 'number of segments'],
        ];
    }

    /**
     * @dataProvider dataProviderMalformedSegments
     */
    public function testMalformed(int $segmentOrder, string $segmentName): void
    {
        $decoder = new ApiKeyDecoder();

        $parts = \explode('.', self::EXAMPLE_API_KEY);
        $parts[$segmentOrder] = 'MI$$ING';

        $this->expectException(InvalidApiKeyException::class);
        $this->expectExceptionMessage("The given API key is invalid. The ${segmentName} is incorrect.");

        $decoder->decode(\implode('.', $parts));
    }
}
