<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run;

use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class LoggerFactory
{
    public function createLogger(): Logger
    {
        $logger = new Logger('swag_paypal_pos');
        $logger->pushHandler(new LogHandler());
        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }
}
