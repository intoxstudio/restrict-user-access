<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'                       => true,
        'array_indentation'            => true,
        'blank_line_after_opening_tag' => true,
        'combine_consecutive_issets'   => true,
        'combine_consecutive_unsets'   => true,
        'class_attributes_separation'  => ['elements' => ['method' => 'one',]],
        'single_quote'                 => true,
        'binary_operator_spaces'       => [
            'default'   => 'single_space',
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ]
        ],
        'braces' => [
            'allow_single_line_closure' => true,
        ],
        'concat_space'         => true,
        'include'              => true,
        'lowercase_cast'       => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'use',
            ]
        ],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_whitespace_in_blank_line'                 => true,
        'object_operator_without_whitespace'          => true,
        'ternary_operator_spaces'                     => true,
        'trim_array_spaces'                           => true,
        'lowercase_static_reference'                  => true,
        'no_superfluous_elseif'                       => true,
        'no_useless_else'                             => true,
        'no_useless_return'                           => true
    ])
    ->setLineEnding(PHP_EOL);
