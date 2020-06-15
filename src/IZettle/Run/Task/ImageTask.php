<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Run\Task;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\ImageSyncer;

class ImageTask extends AbstractTask
{
    private const TASK_NAME_IMAGE = 'image';

    /**
     * @var ImageSyncer
     */
    private $imageSyncer;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        ImageSyncer $imageSyncer
    ) {
        parent::__construct($runService, $logger);
        $this->imageSyncer = $imageSyncer;
    }

    public function getRunTaskName(): string
    {
        return self::TASK_NAME_IMAGE;
    }

    protected function run(SalesChannelEntity $salesChannel, Context $context): void
    {
        $this->imageSyncer->syncImages($this->getIZettleSalesChannel($salesChannel), $context);
    }
}
