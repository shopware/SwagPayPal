<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\Pos\Api\PosBaseURL;
use Swag\PayPal\Pos\Client\TokenClient;

class TokenClientMock extends TokenClient
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->client = new GuzzleClientMock([
            'base_uri' => PosBaseURL::OAUTH,
        ]);
    }
}
