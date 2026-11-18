<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Log\LogKey;
use Swag\PayPal\Util\Log\LogRedactor;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LogRedactor::class)]
class LogRedactorTest extends TestCase
{
    public function testRedactsNestedDenyListKeysCaseInsensitive(): void
    {
        $data = [
            'id' => 'ABC',
            'payer' => [
                'Email_Address' => 'buyer@example.com',
                'name' => ['given_name' => 'A', 'surname' => 'B'],
                'phone' => ['phone_number' => ['national_number' => '123']],
            ],
            'items' => [['name' => 'Product', 'quantity' => 1]],
            'firstName' => 'A',
            'access_token' => 'secret',
        ];

        static::assertSame([
            'id' => 'ABC',
            'payer' => [
                'Email_Address' => '<redacted>',
                'name' => '<redacted>',
                'phone' => '<redacted>',
            ],
            'items' => [['name' => '<redacted>', 'quantity' => 1]],
            'firstName' => '<redacted>',
            'access_token' => '<redacted>',
        ], LogRedactor::redactBody($data));
    }

    public function testKeepsErrorNameAtRootAndRedactsTokenIds(): void
    {
        $data = [
            'name' => 'UNPROCESSABLE_ENTITY',
            'payment_source' => [
                'card' => ['name' => 'Card Holder'],
                'token' => ['id' => 'token-id', 'type' => 'PAYMENT_METHOD_TOKEN'],
                'paypal' => ['attributes' => ['vault' => ['id' => 'vault-id', 'status' => 'VAULTED']]],
            ],
            'client_token' => 'secret',
        ];

        static::assertSame([
            'name' => 'UNPROCESSABLE_ENTITY',
            'payment_source' => [
                'card' => ['name' => '<redacted>'],
                'token' => ['id' => '<redacted>', 'type' => 'PAYMENT_METHOD_TOKEN'],
                'paypal' => ['attributes' => ['vault' => ['id' => '<redacted>', 'status' => 'VAULTED']]],
            ],
            'client_token' => '<redacted>',
        ], LogRedactor::redactBody($data));
    }

    public function testRedactsTheValueOfErrorDetails(): void
    {
        $data = [
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [
                ['field' => '/payer/email_address', 'value' => 'buyer@example.com', 'issue' => 'INVALID_PARAMETER_VALUE'],
            ],
            'nested' => ['deeper' => ['field' => 'a', 'value' => ['b' => 'c']]],
            'other' => ['value' => 'kept'],
        ];

        static::assertSame([
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [
                ['field' => '/payer/email_address', 'value' => '<redacted>', 'issue' => 'INVALID_PARAMETER_VALUE'],
            ],
            'nested' => ['deeper' => ['field' => 'a', 'value' => '<redacted>']],
            'other' => ['value' => 'kept'],
        ], LogRedactor::redactBody($data));
    }

    public function testKeepsCountryAndLastDigitsButRedactsCityAndPostalCode(): void
    {
        $data = [
            'shipping' => ['address' => [
                'address_line_1' => 'Street 1',
                'admin_area_1' => 'NRW',
                'admin_area_2' => 'Schöppingen',
                'postal_code' => '48624',
                'country_code' => 'DE',
            ]],
            'card' => ['last_digits' => '1234', 'number' => '4111111111111111', 'security_code' => '123', 'expiry' => '2030-01'],
            'tax_info' => ['tax_id_type' => 'BR_CPF'],
        ];

        static::assertSame([
            'shipping' => ['address' => [
                'address_line_1' => '<redacted>',
                'admin_area_1' => 'NRW',
                'admin_area_2' => '<redacted>',
                'postal_code' => '<redacted>',
                'country_code' => 'DE',
            ]],
            'card' => ['last_digits' => '1234', 'number' => '<redacted>', 'security_code' => '<redacted>', 'expiry' => '<redacted>'],
            'tax_info' => ['tax_id_type' => 'BR_CPF'],
        ], LogRedactor::redactBody($data));
    }

    public function testRedactPath(): void
    {
        static::assertSame('/v3/vault/payment-tokens/<redacted>', self::redactPath('/v3/vault/payment-tokens/abc123'));
        static::assertSame('/v3/vault/setup-tokens/<redacted>/confirm', self::redactPath('/v3/vault/setup-tokens/abc/confirm'));
        static::assertSame('/v2/checkout/orders/5O1/capture', self::redactPath('/v2/checkout/orders/5O1/capture'));
    }

    public function testKeepsScalarsAndUnrelatedData(): void
    {
        static::assertSame('text', LogRedactor::redactBody('text'));
        static::assertNull(LogRedactor::redactBody(null));
        static::assertSame([1, 2, ['a' => 'b']], LogRedactor::redactBody([1, 2, ['a' => 'b']]));
    }

    public function testRedactHeadersKeepsOnlyAllowList(): void
    {
        static::assertSame([
            'PayPal-Request-Id' => '1',
            'paypal-debug-id' => '2',
            'Content-Type' => 'application/json',
            'date' => 'now',
            'Prefer' => 'return=minimal',
        ], LogRedactor::redactContext([LogKey::REQUEST_HEADERS => [
            'PayPal-Request-Id' => '1',
            'paypal-debug-id' => '2',
            'Content-Type' => 'application/json',
            'date' => 'now',
            'Prefer' => 'return=minimal',
            'Authorization' => 'Bearer x',
            'paypal-auth-assertion' => 'y',
        ]])[LogKey::REQUEST_HEADERS]);
    }

    public function testRedactBodyDecodesJsonStrings(): void
    {
        static::assertSame(
            ['id' => 'ABC', 'payer' => ['email_address' => '<redacted>']],
            LogRedactor::redactBody('{"id":"ABC","payer":{"email_address":"buyer@example.com"}}'),
        );
    }

    public function testRedactBodyRedactsFormBodiesEntirely(): void
    {
        static::assertSame('<redacted>', LogRedactor::redactBody('grant_type=client_credentials&client_secret=secret'));
    }

    public function testRedactBodyKeepsOtherStrings(): void
    {
        static::assertSame('Bad Gateway', LogRedactor::redactBody('Bad Gateway'));
        static::assertSame('', LogRedactor::redactBody(''));
    }

    public function testRedactContextRedactsBodiesHeadersAndPath(): void
    {
        $context = LogRedactor::redactContext([
            LogKey::CODE => 'CODE',
            LogKey::HTTP_PATH => '/v3/vault/payment-tokens/abc',
            LogKey::REQUEST => '{"email":"a@b.c"}',
            LogKey::RESPONSE => ['payer' => ['email' => 'a@b.c']],
            LogKey::PAYLOAD => ['firstName' => 'A'],
            LogKey::REQUEST_HEADERS => ['Authorization' => 'Bearer x', 'Content-Type' => 'application/json'],
            LogKey::RESPONSE_HEADERS => ['paypal-debug-id' => '1', 'Set-Cookie' => 'x'],
        ]);

        static::assertSame([
            LogKey::CODE => 'CODE',
            LogKey::HTTP_PATH => '/v3/vault/payment-tokens/<redacted>',
            LogKey::REQUEST => ['email' => '<redacted>'],
            LogKey::RESPONSE => ['payer' => ['email' => '<redacted>']],
            LogKey::PAYLOAD => ['firstName' => '<redacted>'],
            LogKey::REQUEST_HEADERS => ['Content-Type' => 'application/json'],
            LogKey::RESPONSE_HEADERS => ['paypal-debug-id' => '1'],
        ], $context);
    }

    public function testRedactContextLeavesContextsWithoutRedactedKeysUntouched(): void
    {
        $context = [LogKey::CODE => 'CODE', LogKey::PAYPAL_ORDER_ID => '5O1'];

        static::assertSame($context, LogRedactor::redactContext($context));
    }

    private static function redactPath(string $path): string
    {
        $context = LogRedactor::redactContext([LogKey::HTTP_PATH => $path]);
        static::assertIsString($context[LogKey::HTTP_PATH]);

        return $context[LogKey::HTTP_PATH];
    }
}
