<?php

/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:2.16.1|configurator
 * you can change this configuration by importing this file.
 */
return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        // Each line of multi-line DocComments must have an asterisk [PSR-5] and must be aligned with the first one.
        'align_multiline_comment' => ['comment_type' => 'phpdocs_like'],
        // PHP arrays should be declared using the configured syntax.
        'array_syntax' => ['syntax' => 'short'],
        // Binary operators should be surrounded by space as configured.
        'binary_operator_spaces' => ['default' => 'align_single_space_minimal'],
        // There MUST be one blank line after the namespace declaration.
        'blank_line_after_namespace' => true,
        // Each element of an array must be indented exactly once.
        'array_indentation' => true,
        // Ensure there is no code on the same line as the PHP open tag and it is followed by a blank line.
        'blank_line_after_opening_tag' => true,
        // The body of each structure MUST be enclosed by braces. Braces should be properly placed. Body of braces should be properly indented.
        'braces' => ['allow_single_line_closure' => true, 'position_after_functions_and_oop_constructs' => 'next'],
        // A single space or none should be between cast and variable.
        'cast_spaces' => true,
        // Class, trait and interface elements must be separated with one blank line.
        'class_attributes_separation' => true,
        // Whitespace around the keywords of a class, trait or interfaces definition should be one space.
        'class_definition' => ['single_item_single_line' => true, 'single_line' => true],
        // Using `isset($var) &&` multiple times should be done in one call.
        'combine_consecutive_issets' => true,
        // Calling `unset` on multiple items should be done in one call.
        'combine_consecutive_unsets' => true,
        // Remove extra spaces in a nullable typehint.
        'compact_nullable_typehint' => true,
        // Concatenation should be spaced according configuration.
        'concat_space' => ['spacing' => 'one'],
        // The PHP constants `true`, `false`, and `null` MUST be written using the correct casing.
        'constant_case' => true,
        // Equal sign in declare statement should be surrounded by spaces or not following configuration.
        'declare_equal_normalize' => ['space' => 'single'],
        // Replaces `dirname(__FILE__)` expression with equivalent `__DIR__` constant.
        'dir_constant' => true,
        // PHP code MUST use only UTF-8 without BOM (remove BOM).
        'encoding' => true,
        // Replace deprecated `ereg` regular expression functions with `preg`.
        'ereg_to_preg' => true,
        // Escape implicit backslashes in strings and heredocs to ease the understanding of which are special chars interpreted by PHP and which not.
        'escape_implicit_backslashes' => true,
        // Add curly braces to indirect variables to make them clear to understand. Requires PHP >= 7.0.
        'explicit_indirect_variable' => true,
        // Converts implicit variables into explicit ones in double-quoted strings or heredoc syntax.
        'explicit_string_variable' => true,
        // PHP code must use the long `<?php` tags or short-echo `<?=` tags and not other tag variations.
        'full_opening_tag' => true,
        // Transforms imported FQCN parameters and return types in function arguments to short version.
        'fully_qualified_strict_types' => true,
        // Spaces should be properly placed in a function declaration.
        'function_declaration' => ['closure_function_spacing' => 'one'],
        // Ensure single space between function's argument and its typehint.
        'function_typehint_space' => true,
        // Pre- or post-increment and decrement operators should be used if possible.
        'increment_style' => true,
        // Code MUST use configured indentation type.
        'indentation_type' => true,
        // All PHP files must use same line ending.
        'line_ending' => true,
        // Use `&&` and `||` logical operators instead of `and` and `or`.
        'logical_operators' => true,
        // PHP keywords MUST be in lower case.
        'lowercase_keywords' => true,
        // Class static references `self`, `static` and `parent` MUST be in lower case.
        'lowercase_static_reference' => true,
        // Magic constants should be referred to using the correct casing.
        'magic_constant_casing' => true,
        // Magic method definitions and calls must be using the correct casing.
        'magic_method_casing' => true,
        // Replaces `intval`, `floatval`, `doubleval`, `strval` and `boolval` function calls with according type casting operator.
        'modernize_types_casting' => true,
        // DocBlocks must start with two asterisks, multiline comments must start with a single asterisk, after the opening slash. Both must end with a single asterisk before the closing slash.
        'multiline_comment_opening_closing' => true,
        // Forbid multi-line whitespace before the closing semicolon or move the semicolon to the new line for chained calls.
        'multiline_whitespace_before_semicolons' => true,
        // Add leading `\` before constant invocation of internal constant to speed up resolving. Constant name match is case-sensitive, except for `null`, `false` and `true`.
        'native_constant_invocation' => false,
        // Function defined by PHP should be called using the correct casing.
        'native_function_casing' => true,
        // Add leading `\` before function invocation to speed up resolving.
        'native_function_invocation' => false,
        // Native type hints for functions should use the correct case.
        'native_function_type_declaration_casing' => true,
        // There should be no empty lines after class opening brace.
        'no_blank_lines_after_class_opening' => true,
        // There should not be blank lines between docblock and the documented element.
        'no_blank_lines_after_phpdoc' => true,
        // There must be a comment when fall-through is intentional in a non-empty case body.
        'no_break_comment' => true,
        // The closing `? >` tag MUST be omitted from files containing only PHP.
        'no_closing_tag' => true,
        // There should not be any empty comments.
        'no_empty_comment' => true,
        // There should not be empty PHPDoc blocks.
        'no_empty_phpdoc' => true,
        // Remove useless semicolon statements.
        'no_empty_statement' => true,
        // Removes extra blank lines and/or blank lines following configuration.
        'no_extra_blank_lines' => true,
        // Remove leading slashes in `use` clauses.
        'no_leading_import_slash' => true,
        // The namespace declaration line shouldn't contain leading whitespace.
        'no_leading_namespace_whitespace' => true,
        // Either language construct `print` or `echo` should be used.
        'no_mixed_echo_print' => true,
        // Operator `=>` should not be surrounded by multi-line whitespaces.
        'no_multiline_whitespace_around_double_arrow' => true,
        // Properties MUST not be explicitly initialized with `null` except when they have a type declaration (PHP 7.4).
        'no_null_property_initialization' => true,
        // Short cast `bool` using double exclamation mark should not be used.
        'no_short_bool_cast' => true,
        // Replace short-echo `<?=` with long format `<?php echo` syntax.
        'no_short_echo_tag' => true,
        // Single-line whitespace before closing semicolon are prohibited.
        'no_singleline_whitespace_before_semicolons' => true,
        // When making a method or function call, there MUST NOT be a space between the method or function name and the opening parenthesis.
        'no_spaces_after_function_name' => true,
        // There MUST NOT be spaces around offset braces.
        'no_spaces_around_offset' => true,
        // There MUST NOT be a space after the opening parenthesis. There MUST NOT be a space before the closing parenthesis.
        'no_spaces_inside_parenthesis' => true,
        // Replaces superfluous `elseif` with `if`.
        'no_superfluous_elseif' => true,
        // Remove trailing commas in list function calls.
        'no_trailing_comma_in_list_call' => true,
        // PHP single-line arrays should not have trailing comma.
        'no_trailing_comma_in_singleline_array' => true,
        // Remove trailing whitespace at the end of non-blank lines.
        'no_trailing_whitespace' => true,
        // There MUST be no trailing spaces inside comment or PHPDoc.
        'no_trailing_whitespace_in_comment' => true,
        // Removes unneeded parentheses around control statements.
        'no_unneeded_control_parentheses' => true,
        // Removes unneeded curly braces that are superfluous and aren't part of a control structure's body.
        'no_unneeded_curly_braces' => true,
        // In function arguments there must not be arguments with default values before non-default ones.
        'no_unreachable_default_argument_value' => true,
        // Variables must be set `null` instead of using `(unset)` casting.
        'no_unset_cast' => true,
        // Properties should be set to `null` instead of using `unset`.
        'no_unset_on_property' => true,
        // Unused `use` statements must be removed.
        'no_unused_imports' => true,
        // There should not be useless `else` cases.
        'no_useless_else' => true,
        // There should not be an empty `return` statement at the end of a function.
        'no_useless_return' => true,
        // In array declaration, there MUST NOT be a whitespace before each comma.
        'no_whitespace_before_comma_in_array' => true,
        // Remove trailing whitespace at the end of blank lines.
        'no_whitespace_in_blank_line' => true,
        // Remove Zero-width space (ZWSP), Non-breaking space (NBSP) and other invisible unicode symbols.
        'non_printable_character' => true,
        // Array index should always be written by using square braces.
        'normalize_index_brace' => true,
        // There should not be space before or after object `T_OBJECT_OPERATOR` `->`.
        'object_operator_without_whitespace' => true,
        // Ordering `use` statements.
        'ordered_imports' => true,
        // Orders the elements of classes/interfaces/traits.
        'ordered_class_elements' => true,
        // PHPUnit assertion method calls like `->assertSame(true, $foo)` should be written with dedicated method like `->assertTrue($foo)`.
        'php_unit_construct' => true,
        // PHPUnit annotations should be a FQCNs including a root namespace.
        'php_unit_fqcn_annotation' => true,
        // Enforce camel (or snake) case for PHPUnit test methods, following configuration.
        'php_unit_method_casing' => true,
        // Usage of PHPUnit's mock e.g. `->will($this->returnValue(..))` must be replaced by its shorter equivalent such as `->willReturn(...)`.
        'php_unit_mock_short_will_return' => true,
        // Order `@covers` annotation of PHPUnit tests.
        'php_unit_ordered_covers' => true,
        // Changes the visibility of the `setUp()` and `tearDown()` functions of PHPUnit to `protected`, to match the PHPUnit TestCase.
        'php_unit_set_up_tear_down_visibility' => true,
        // PHPUnit methods like `assertSame` should be used instead of `assertEquals`.
        'php_unit_strict' => true,
        // Calls to `PHPUnit\Framework\TestCase` static methods must all be of the same type, either `$this->`, `self::` or `static::`.
        'php_unit_test_case_static_method_calls' => true,
        // PHPDoc should contain `@param` for all params.
        'phpdoc_add_missing_param_annotation' => true,
        // All items of the given phpdoc tags must be either left-aligned or (by default) aligned vertically.
        'phpdoc_align' => true,
        // Docblocks should have the same indentation as the documented subject.
        'phpdoc_indent' => true,
        // Fix PHPDoc inline tags, make `@inheritdoc` always inline.
        'phpdoc_inline_tag' => true,
        // `@access` annotations should be omitted from PHPDoc.
        'phpdoc_no_access' => true,
        // No alias PHPDoc tags should be used.
        'phpdoc_no_alias_tag' => true,
        // Annotations in PHPDoc should be ordered so that `@param` annotations come first, then `@throws` annotations, then `@return` annotations.
        'phpdoc_order' => true,
        // The type of `@return` annotations of methods returning a reference to itself must the configured one.
        'phpdoc_return_self_reference' => true,
        // Scalar types should always be written in the same form. `int` not `integer`, `bool` not `boolean`, `float` not `real` or `double`.
        'phpdoc_scalar' => true,
        // Annotations in PHPDoc should be grouped together so that annotations of the same type immediately follow each other, and annotations of a different type are separated by a single blank line.
        'phpdoc_separation' => true,
        // Single line `@var` PHPDoc should have proper spacing.
        'phpdoc_single_line_var_spacing' => true,
        // Removes extra blank lines after summary and after description in PHPDoc.
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        // The correct case must be used for standard PHP types in PHPDoc.
        'phpdoc_types' => true,
        // Sorts PHPDoc types.
        'phpdoc_types_order' => true,
        // `@var` and `@type` annotations must have type and name in the correct order.
        'phpdoc_var_annotation_correct_order' => true,
        // Class names should match the file name.
        'psr4' => true,
        // There should be one or no space before colon, and one space after it in return type declarations, according to configuration.
        'return_type_declaration' => true,
        // Inside class or interface element `self` should be preferred to the class name itself.
        'self_accessor' => true,
        // Instructions must be terminated with a semicolon.
        'semicolon_after_instruction' => true,
        // Cast shall be used, not `settype`.
        'set_type_to_cast' => true,
        // Cast `(boolean)` and `(integer)` should be written as `(bool)` and `(int)`, `(double)` and `(real)` as `(float)`, `(binary)` as `(string)`.
        'short_scalar_cast' => true,
        // Converts explicit variables in double-quoted strings and heredoc syntax from simple to complex format (`${` to `{$`).
        'simple_to_complex_string_variable' => true,
        // There should be exactly one blank line before a namespace declaration.
        'single_blank_line_before_namespace' => true,
        // There MUST be one use keyword per declaration.
        'single_import_per_statement' => true,
        // There MUST NOT be more than one property or constant declared per statement.
        'single_class_element_per_statement' => true,
        // Each namespace use MUST go on its own line and there MUST be one blank line after the use statements block.
        'single_line_after_imports' => true,
        // Single-line comments and multi-line comments with only one line of actual content should use the `//` syntax.
        'single_line_comment_style' => true,
        // Convert double quotes to single quotes for simple strings.
        'single_quote' => true,
        // Each trait `use` must be done as single statement.
        'single_trait_insert_per_statement' => true,
        // Fix whitespace after a semicolon.
        'space_after_semicolon' => true,
        // Increment and decrement operators should be used if possible.
        'standardize_increment' => true,
        // Replace all `<>` with `!=`.
        'standardize_not_equals' => true,
        // A case should be followed by a colon and not a semicolon.
        'switch_case_semicolon_to_colon' => true,
        // Removes extra spaces between colon and case value.
        'switch_case_space' => true,
        // Standardize spaces around ternary operator.
        'ternary_operator_spaces' => true,
        // PHP multi-line arrays should have a trailing comma.
        'trailing_comma_in_multiline_array' => true,
        // Unary operators should be placed adjacent to their operands.
        'unary_operator_spaces' => true,
        // Visibility MUST be declared on all properties and methods; `abstract` and `final` MUST be declared before the visibility; `static` MUST be declared after the visibility.
        'visibility_required' => true,
        // In array declaration, there MUST be a whitespace after each comma.
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder(PhpCsFixer\Finder::create()
        ->exclude('vendor')
        ->exclude('var')
        ->in(__DIR__)
    );
