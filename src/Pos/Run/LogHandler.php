<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run;

use Monolog\Handler\AbstractProcessingHandler;
use Shopware\Core\Content\Product\ProductEntity;

class LogHandler extends AbstractProcessingHandler
{
    /**
     * @var mixed[]
     */
    private $logs;

    public function __construct()
    {
        parent::__construct();
        $this->logs = [];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function flush(): void
    {
        $this->logs = [];
    }

    protected function write(array $record): void
    {
        $update = [
            'level' => $record['level'],
            'message' => $record['message'],
        ];

        if (isset($record['context']['product'])) {
            $product = $record['context']['product'];
            if ($product instanceof ProductEntity) {
                $update['productId'] = $product->getParentId() ?? $product->getId();
                $update['productVersionId'] = $product->getVersionId();
            }
        }

        $this->logs[] = $update;
    }
}
