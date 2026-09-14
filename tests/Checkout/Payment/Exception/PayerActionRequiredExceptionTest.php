<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V1\Common\Link;
use Shopware\PayPalSDK\Struct\V1\Common\LinkCollection;
use Shopware\PayPalSDK\Struct\V2\Common\Link as V2Link;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PayerActionRequiredException::class)]
class PayerActionRequiredExceptionTest extends TestCase
{
    public function testGetPayerActionUrl(): void
    {
        $exception = PayerActionRequiredException::payerActionRequired('paypalOrderId', new LinkCollection([
            (new Link())->assign(['rel' => 'self', 'href' => 'https://paypal.test/self']),
            (new Link())->assign(['rel' => V2Link::RELATION_PAYER_ACTION, 'href' => 'https://paypal.test/payer-action']),
        ]));

        static::assertSame('https://paypal.test/payer-action', $exception->getPayerActionUrl());
    }

    public function testGetPayerActionUrlWithoutLinks(): void
    {
        static::assertNull(PayerActionRequiredException::payerActionRequired('paypalOrderId')->getPayerActionUrl());
    }

    /**
     * Link::$rel and Link::$href are typed without a default, so reading an omitted one is a fatal error.
     *
     * @param array<string, string> $link
     */
    #[DataProvider('dataProviderIncompleteLinks')]
    public function testGetPayerActionUrlToleratesIncompleteLinks(array $link): void
    {
        $exception = PayerActionRequiredException::payerActionRequired('paypalOrderId', new LinkCollection([
            (new Link())->assign($link),
            (new Link())->assign(['rel' => V2Link::RELATION_PAYER_ACTION, 'href' => 'https://paypal.test/payer-action']),
        ]));

        static::assertSame('https://paypal.test/payer-action', $exception->getPayerActionUrl());
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function dataProviderIncompleteLinks(): array
    {
        return [
            'without rel' => [['href' => 'https://paypal.test/self']],
            'without href' => [['rel' => 'self']],
            'without either' => [['method' => 'GET']],
        ];
    }
}
