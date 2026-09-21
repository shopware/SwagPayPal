<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Swag\PayPal\Migration\Migration1789983699AddProcessTransitionFromUnconfirmed;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Migration1789983699AddProcessTransitionFromUnconfirmed::class)]
class Migration1789983699AddProcessTransitionFromUnconfirmedTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private Connection $connection;

    private string $stateMachineId;

    protected function setUp(): void
    {
        $this->connection = $this->getContainer()->get(Connection::class);

        $stateMachineId = $this->connection->fetchOne(
            'SELECT `id` FROM `state_machine` WHERE `technical_name` = :technicalName',
            ['technicalName' => OrderTransactionStates::STATE_MACHINE]
        );
        static::assertIsString($stateMachineId);
        $this->stateMachineId = $stateMachineId;
    }

    public function testUpdateAddsTheTransition(): void
    {
        $this->removeTransition();

        (new Migration1789983699AddProcessTransitionFromUnconfirmed())->update($this->connection);

        $transitions = $this->getTransitions();
        static::assertCount(1, $transitions);
        static::assertSame($this->getStateId(OrderTransactionStates::STATE_IN_PROGRESS), $transitions[0]['to_state_id']);
    }

    public function testUpdateCanBeExecutedTwiceWithoutSideEffects(): void
    {
        $this->removeTransition();

        $migration = new Migration1789983699AddProcessTransitionFromUnconfirmed();

        $migration->update($this->connection);
        $afterFirstRun = $this->getTransitions();

        $migration->update($this->connection);

        // the second run must neither add a row nor touch the one written by the first run
        static::assertSame($afterFirstRun, $this->getTransitions());
    }

    private function removeTransition(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM `state_machine_transition`
             WHERE `state_machine_id` = :stateMachineId
                 AND `action_name` = :actionName
                 AND `from_state_id` = :fromStateId',
            [
                'stateMachineId' => $this->stateMachineId,
                'actionName' => StateMachineTransitionActions::ACTION_PROCESS,
                'fromStateId' => $this->getStateId(OrderTransactionStates::STATE_UNCONFIRMED),
            ]
        );
    }

    /**
     * @return list<array{id: string, to_state_id: string, created_at: string}>
     */
    private function getTransitions(): array
    {
        /** @var list<array{id: string, to_state_id: string, created_at: string}> $transitions */
        $transitions = $this->connection->fetchAllAssociative(
            'SELECT `id`, `to_state_id`, `created_at` FROM `state_machine_transition`
             WHERE `state_machine_id` = :stateMachineId
                 AND `action_name` = :actionName
                 AND `from_state_id` = :fromStateId
             ORDER BY `id`',
            [
                'stateMachineId' => $this->stateMachineId,
                'actionName' => StateMachineTransitionActions::ACTION_PROCESS,
                'fromStateId' => $this->getStateId(OrderTransactionStates::STATE_UNCONFIRMED),
            ]
        );

        return $transitions;
    }

    private function getStateId(string $technicalName): string
    {
        $stateId = $this->connection->fetchOne(
            'SELECT `id` FROM `state_machine_state`
             WHERE `state_machine_id` = :stateMachineId AND `technical_name` = :technicalName',
            [
                'stateMachineId' => $this->stateMachineId,
                'technicalName' => $technicalName,
            ]
        );
        static::assertIsString($stateId);

        return $stateId;
    }
}
