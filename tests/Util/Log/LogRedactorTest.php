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

    public function testMatchesSnakeCaseAndCamelCaseKeysAlike(): void
    {
        $data = [
            'phoneNumber' => '+49 123',
            'additionalAddressLine1' => 'c/o Doe',
            'zip_code' => '48624',
            'city' => 'Schöppingen',
            'birthday' => '1990-01-01',
            'returnUrl' => 'https://shop.example/paypal/restore/token',
            'orderNumber' => '10023',
        ];

        static::assertSame([
            'phoneNumber' => '<redacted>',
            'additionalAddressLine1' => '<redacted>',
            'zip_code' => '<redacted>',
            'city' => '<redacted>',
            'birthday' => '<redacted>',
            'returnUrl' => 'https://shop.example/<redacted>',
            'orderNumber' => '10023',
        ], LogRedactor::redactBody($data));
    }

    public function testRedactsVenmoUserNameMerchantEmailAndAgentAddress(): void
    {
        static::assertSame([
            'payment_source' => ['venmo' => ['user_name' => '<redacted>']],
            'primary_email' => '<redacted>',
            'context' => ['provided_address' => '<redacted>'],
        ], LogRedactor::redactBody([
            'payment_source' => ['venmo' => ['user_name' => '@jane-doe']],
            'primary_email' => 'merchant@example.com',
            'context' => ['provided_address' => ['address_line_1' => 'Street 1']],
        ]));
    }

    public function testRedactsTheTokenOfVaultEventsAndLinksToIt(): void
    {
        $event = [
            'id' => 'WH-1',
            'event_type' => 'VAULT.PAYMENT-TOKEN.CREATED',
            'resource' => [
                'id' => '8kk8451t',
                'customer' => ['id' => 'customer-id'],
                'links' => [['href' => 'https://api-m.paypal.com/v3/vault/payment-tokens/8kk8451t', 'rel' => 'self']],
            ],
        ];

        static::assertSame([
            'id' => 'WH-1',
            'event_type' => 'VAULT.PAYMENT-TOKEN.CREATED',
            'resource' => [
                'id' => '<redacted>',
                'customer' => ['id' => 'customer-id'],
                'links' => [['href' => 'https://api-m.paypal.com/v3/vault/payment-tokens/<redacted>', 'rel' => 'self']],
            ],
        ], LogRedactor::redactBody($event));
    }

    public function testKeepsTheResourceIdOfOtherEvents(): void
    {
        $event = [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAP-1',
                'links' => [['href' => 'https://api-m.paypal.com/v2/payments/captures/CAP-1', 'rel' => 'self']],
            ],
        ];

        static::assertSame($event, LogRedactor::redactBody($event));
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

    public function testUrlsKeepOnlySchemeAndHost(): void
    {
        static::assertSame([
            'experience_context' => [
                'return_url' => 'https://shop.example/<redacted>',
                'cancel_url' => 'https://shop.example/<redacted>',
                'order_update_callback_config' => ['callback_url' => 'https://shop.example/<redacted>'],
            ],
            'url' => 'https://shop.example/<redacted>',
            'links' => [['href' => 'https://api-m.paypal.com/v2/checkout/orders/5O1']],
        ], LogRedactor::redactBody([
            'experience_context' => [
                'return_url' => 'https://shop.example/paypal/restore-context?token=secret',
                'cancel_url' => 'https://shop.example/paypal/restore-context?token=secret&cancel=1',
                'order_update_callback_config' => ['callback_url' => 'https://shop.example/store-api/paypal/express/shipping-callback/sc/secret'],
            ],
            'url' => 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=secret',
            'links' => [['href' => 'https://api-m.paypal.com/v2/checkout/orders/5O1']],
        ]));
    }

    public function testUrlsSetByPatchesKeepOnlySchemeAndHost(): void
    {
        static::assertSame(
            [['op' => 'replace', 'path' => '/url', 'value' => 'https://shop.example/<redacted>']],
            LogRedactor::redactBody([['op' => 'replace', 'path' => '/url', 'value' => 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=secret']]),
        );
    }

    public function testUrlKeysWithoutAUrlAreRedactedEntirely(): void
    {
        static::assertSame(['return_url' => '<redacted>'], LogRedactor::redactBody(['return_url' => '/relative?token=secret']));
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

    private static function redactPath(string $path): string
    {
        return LogRedactor::redactPath($path);
    }
}
