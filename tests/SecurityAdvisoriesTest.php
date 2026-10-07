<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\SecurityAdvisories;

/**
 * @internal
 */
#[Package('checkout')]
class SecurityAdvisoriesTest extends TestCase
{
    public function testIsFixed(): void
    {
        static::assertTrue(SecurityAdvisories::isFixed('GHSA-mwvm-68w4-32gq'));
        static::assertTrue(SecurityAdvisories::isFixed('ghsa-mwvm-68w4-32gq'));
        static::assertFalse(SecurityAdvisories::isFixed('GHSA-xxxx-xxxx-xxxx'));
    }
}
