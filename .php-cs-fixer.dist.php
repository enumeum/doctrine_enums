<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$header = <<<'HEADER'
This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
(c) Alexey Sitka <alexey.sitka@gmail.com>
For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->append([__FILE__])
    ->exclude([]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'error_suppression' => true,
        'global_namespace_import' => ['import_classes' => true, 'import_functions' => true],
        'header_comment' => ['header' => $header],
        'is_null' => false,
        'list_syntax' => ['syntax' => 'short'],
        'modernize_types_casting' => true,
        'no_homoglyph_names' => true,
        'no_null_property_initialization' => true,
        'no_superfluous_elseif' => true,
        'no_unset_on_property' => true,
        'no_useless_else' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha', 'imports_order' => ['class', 'function', 'const']],
        'phpdoc_separation' => [
            'groups' => [
                ['ORM\\*'],
                ['Assert\\*'],
                ['deprecated', 'link', 'see', 'since'],
                ['author', 'copyright', 'license'],
                ['category', 'package', 'subpackage'],
                ['property', 'property-read', 'property-write'],
            ],
        ],
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => ['target' => 'newest'],
        'php_unit_dedicate_assert_internal_type' => ['target' => 'newest'],
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'php_unit_mock' => ['target' => 'newest'],
        'php_unit_namespaced' => ['target' => 'newest'],
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_strict' => true,
        'php_unit_test_annotation' => ['style' => 'prefix'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
        'psr_autoloading' => true,
        'random_api_migration' => true,
        'self_accessor' => true,
        'strict_param' => true,
        'ternary_to_null_coalescing' => true,
        'trailing_comma_in_multiline' => ['after_heredoc' => true, 'elements' => ['arguments', 'arrays', 'match', 'parameters']],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
