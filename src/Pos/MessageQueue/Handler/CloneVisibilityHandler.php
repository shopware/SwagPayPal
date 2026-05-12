<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\AbstractSyncHandler;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\CloneVisibilityMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Run\RunService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler(handles: CloneVisibilityMessage::class)]
class CloneVisibilityHandler extends AbstractSyncHandler
{
    private EntityRepository $productVisibilityRepository;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        MessageDispatcher $messageBus,
        MessageHydrator $messageHydrator,
        EntityRepository $productVisibilityRepository,
    ) {
        parent::__construct($runService, $logger, $messageBus, $messageHydrator);
        $this->productVisibilityRepository = $productVisibilityRepository;
    }

    /**
     * @param CloneVisibilityMessage $message
     */
    public function sync(AbstractSyncMessage $message): void
    {
        $context = $message->getContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $message->getFromSalesChannelId()));
        $criteria->setLimit($message->getLimit());
        $criteria->setOffset($message->getOffset());

        /** @var ProductVisibilityCollection $existingVisibilities */
        $existingVisibilities = $this->productVisibilityRepository->search($criteria, $context)->getEntities();

        $updates = [];
        foreach ($existingVisibilities as $existingVisibility) {
            $updates[] = [
                'productId' => $existingVisibility->getProductId(),
                'salesChannelId' => $message->getToSalesChannelId(),
                'visibility' => $existingVisibility->getVisibility(),
            ];
        }

        $this->productVisibilityRepository->upsert($updates, $context);
    }
}
