<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Context;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;

class ACDCMethodData extends AbstractMethodData
{
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'Kredit- oder Debitkarte',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Credit or debit card',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -98;
    }

    /**
     * @return class-string
     */
    public function getHandler(): string
    {
        return ACDCHandler::class;
    }

    public function getRuleData(Context $context): ?array
    {
        return null;
    }

    public function getInitialState(): bool
    {
        // will be set to true upon official release (update procedure has to be added)
        return false;
    }
}
