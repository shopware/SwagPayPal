<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Shopware\Core\Content\Product\ProductEntity;

$parameter = new \ReflectionParameter([AbstractProcessingHandler::class, 'write'], 'record');
$type = $parameter->getType();
if ($type instanceof \ReflectionNamedType && $type->getName() === 'array') {
    class LogHandler extends AbstractProcessingHandler
    {
        /**
         * @var mixed[][]
         */
        private array $logs;

        /**
         * @internal
         */
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
} else {
    class LogHandler extends AbstractProcessingHandler
    {
        /**
         * @var array<string, mixed>[]
         */
        private array $logs;

        /**
         * @internal
         */
        public function __construct()
        {
            parent::__construct();
            $this->logs = [];
        }

            /**
             * @return array<string, mixed>[]
             */
            public function getLogs(): array
            {
                return $this->logs;
            }

            public function flush(): void
            {
                $this->logs = [];
            }

            protected function write(LogRecord $record): void
            {
                $update = [
                    'level' => $record->level->value,
                    'message' => $record->message,
                ];

                if (isset($record->context['product'])) {
                    $product = $record->context['product'];
                    if ($product instanceof ProductEntity) {
                        $update['productId'] = $product->getParentId() ?? $product->getId();
                        $update['productVersionId'] = $product->getVersionId();
                    }
                }

                $this->logs[] = $update;
            }
    }
}
