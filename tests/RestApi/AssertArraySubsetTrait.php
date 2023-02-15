<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
trait AssertArraySubsetTrait
{
    public static function assertArraySubset(array $subset, array $fullArray): void
    {
        foreach ($subset as $key => $value) {
            TestCase::assertArrayHasKey($key, $fullArray);

            if (\is_array($subset[$key])) {
                static::assertArraySubset($fullArray[$key], $subset[$key]);
            } else {
                TestCase::assertSame($value, $fullArray[$key]);
            }
        }
    }
}
