<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Component\Patch;

interface PatchInterface
{
    public const OPERATION_ADD = 'add';
    public const OPERATION_REPLACE = 'replace';

    /**
     * Returns the operation that should be triggered.
     */
    public function getOperation(): string;

    /**
     * Returns the path for the patch call
     */
    public function getPath(): string;

    /**
     * Returns the value that should be transferred to PayPal
     */
    public function getValue(): array;
}
