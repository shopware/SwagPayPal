<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DevOps\LogCallRuleFixture;

use Psr\Log\LoggerInterface;

class Calls
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function run(string $dynamic): void
    {
        $this->logger->alert('Ignored in DevOps ' . $dynamic);
    }
}
