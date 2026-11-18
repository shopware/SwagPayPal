<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook\LogCallRuleFixture;

use Psr\Log\LoggerInterface;

class Calls
{
    public function __construct(private readonly LoggerInterface $logger, private readonly \stdClass $product)
    {
    }

    public function run(): void
    {
        $this->logger->info('The Zettle webhook needs a code');
        $this->logger->debug('Product', ['product' => $this->product]);
    }
}
