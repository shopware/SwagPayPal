<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Run\Task;

use Swag\PayPal\IZettle\MessageQueue\Handler\SyncManagerHandler;

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
