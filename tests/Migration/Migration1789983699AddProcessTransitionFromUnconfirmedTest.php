<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Migration;

use Doctrine\DBAL\ArrayParameterType;
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

    private const ACTIONS = [
        StateMachineTransitionActions::ACTION_DO_PAY,
        StateMachineTransitionActions::ACTION_PROCESS,
    ];

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

    public function testUpdateAddsTheTransitionForBothActions(): void
    {
        $this->removeTransitions();

        (new Migration1789983699AddProcessTransitionFromUnconfirmed())->update($this->connection);

        $inProgressStateId = $this->getStateId(OrderTransactionStates::STATE_IN_PROGRESS);
        foreach (self::ACTIONS as $actionName) {
            $transitions = $this->getTransitions($actionName);
            static::assertCount(1, $transitions, $actionName);
            static::assertSame($inProgressStateId, $transitions[0]['to_state_id'], $actionName);
        }
    }

    public function testUpdateAddsMissingActionIfOtherOneExists(): void
    {
        $this->removeTransitions();

        $migration = new Migration1789983699AddProcessTransitionFromUnconfirmed();
        $migration->update($this->connection);
        $processTransitions = $this->getTransitions(StateMachineTransitionActions::ACTION_PROCESS);

        $this->removeTransitions([StateMachineTransitionActions::ACTION_DO_PAY]);
        $migration->update($this->connection);

        static::assertCount(1, $this->getTransitions(StateMachineTransitionActions::ACTION_DO_PAY));
        static::assertSame($processTransitions, $this->getTransitions(StateMachineTransitionActions::ACTION_PROCESS));
    }

    public function testUpdateCanBeExecutedTwiceWithoutSideEffects(): void
    {
        $this->removeTransitions();

        $migration = new Migration1789983699AddProcessTransitionFromUnconfirmed();

        $migration->update($this->connection);
        $afterFirstRun = \array_map($this->getTransitions(...), self::ACTIONS);

        $migration->update($this->connection);

        // the second run must neither add a row nor touch the ones written by the first run
        static::assertSame($afterFirstRun, \array_map($this->getTransitions(...), self::ACTIONS));
    }

    /**
     * @param list<string> $actionNames
     */
    private function removeTransitions(array $actionNames = self::ACTIONS): void
    {
        $this->connection->executeStatement(
            'DELETE FROM `state_machine_transition`
             WHERE `state_machine_id` = :stateMachineId
                 AND `action_name` IN (:actionNames)
                 AND `from_state_id` = :fromStateId',
            [
                'stateMachineId' => $this->stateMachineId,
                'actionNames' => $actionNames,
                'fromStateId' => $this->getStateId(OrderTransactionStates::STATE_UNCONFIRMED),
            ],
            ['actionNames' => ArrayParameterType::STRING]
        );
    }

    /**
     * @return list<array{id: string, to_state_id: string, created_at: string}>
     */
    private function getTransitions(string $actionName): array
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
                'actionName' => $actionName,
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
