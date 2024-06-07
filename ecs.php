<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpCsFixer\Fixer\Alias\MbStrFunctionsFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedTraitsFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    (include __DIR__ . '/../../../ecs.php')($ecsConfig);

    $ecsConfig->ruleWithConfiguration(HeaderCommentFixer::class, ['header' => '(c) shopware AG <info@shopware.com>
For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.', 'separate' => 'bottom', 'location' => 'after_declare_strict', 'comment_type' => 'comment']);
    $ecsConfig->ruleWithConfiguration(NativeFunctionInvocationFixer::class, [
        'include' => [NativeFunctionInvocationFixer::SET_ALL],
        'scope' => 'namespaced',
    ]);
    $ecsConfig->rule(MbStrFunctionsFixer::class);

    // With PHP 8.3.8 it's necessary to have inherited traits in the correct order
    $ecsConfig->skip([OrderedTraitsFixer::class]);

    $ecsConfig->cacheDirectory(__DIR__ . '/var/cache/cs_fixer');
    $ecsConfig->cacheNamespace('SwagPayPal');
    $ecsConfig->paths([__DIR__ . '/src', __DIR__ . '/tests']);
};
