<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Webhook\WebhookHandler;

/**
 * @implements \IteratorAggregate<\Swag\PayPal\Webhook\WebhookHandler>
 */
/**
 * @internal
 */
#[Package('checkout')]
class DummyCollection implements \IteratorAggregate
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return \ArrayIterator<array-key, WebhookHandler>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }
}
