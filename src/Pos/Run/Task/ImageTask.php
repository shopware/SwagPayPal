<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run\Task;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;

#[Package('checkout')]
class ImageTask extends AbstractTask
{
    private const TASK_NAME_IMAGE = 'image';

    public function getRunTaskName(): string
    {
        return self::TASK_NAME_IMAGE;
    }

    public function getSteps(): array
    {
        return [
            SyncManagerHandler::SYNC_IMAGE,
        ];
    }
}
