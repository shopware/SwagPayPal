<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\LogCallRuleFixture;

use Psr\Log\LoggerInterface;
use Swag\PayPal\Util\Log\LogKey;

class Calls
{
    public function __construct(private readonly LoggerInterface $logger, private readonly \stdClass $product)
    {
    }

    public function run(\Throwable $e): void
    {
        $this->logger->info('The POS run log needs no code');
        $this->logger->error('Could not do it', [LogKey::EXCEPTION => $e, 'product' => $this->product, 'productName' => 'name']);
        $this->logger->error('Could not do it', ['exception' => $e]);
        $this->logger->debug('Object', ['order' => $this->product]);
        $this->logger->alert('Alert');
        $this->logger->debug('Object key', [LogKey::PAYPAL_ORDER => $this->product]);
    }
}
