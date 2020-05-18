<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Util;

use Shopware\Core\Framework\Context;
use Swag\PayPal\Util\LocaleCodeProvider;

class LocaleCodeProviderMock extends LocaleCodeProvider
{
    public function getLocaleCodeFromContext(Context $context): string
    {
        return 'en-GB';
    }
}
