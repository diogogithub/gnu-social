<?php

declare(strict_types = 1);

namespace App\Util\Form;

use function App\Core\I18n\_m;
use App\Entity\Actor;
use Component\Language\Entity\Language;
use App\Util\Common;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

abstract class FormFields
{
    /**
     * Create a form field for asking for a new password twice (ensuring they're the same)
     */
    public static function repeated_password(array $options = []): array
    {
        $constraints = ($options['required'] ?? true)
        ? [
            new NotBlank(['message' => _m('Please enter a password')]),
            new Length([
                'min' => Common::config('password', 'min_length'), 'minMessage' => _m(['Your password should be at least # characters'], ['count' => Common::config('password', 'min_length')]),
                'max' => Common::config('password', 'max_length'), 'maxMessage' => _m(['Your password should be at most # characters'], ['count' => Common::config('password', 'max_length')]), ]),
        ] : [];

        return [
            'password', RepeatedType::class,
            [
                'type'          => PasswordType::class,
                'first_options' => [
                    'label'       => _m('Password'),
                    'label_attr'  => ['class' => 'section-form-label'],
                    'attr'        => array_merge(['placeholder' => _m('********'), 'required' => $options['required'] ?? true], $options['attr'] ?? []),
                    'constraints' => $constraints,
                    'help'        => _m('Write a password with at least {min_length} characters, and a maximum of {max_length}.', ['min_length' => Common::config('password', 'min_length'), 'max_length' => Common::config('password', 'max_length')]),
                ],
                'second_options' => [
                    'label'       => _m('Repeat Password'),
                    'label_attr'  => ['class' => 'section-form-label'],
                    'attr'        => array_merge(['placeholder' => _m('********'), 'required' => $options['required'] ?? true], $options['attr'] ?? []),
                    'help'        => _m('Confirm your password.'),
                    'required'    => $options['required'] ?? true,
                    'constraints' => $constraints,
                ],
                'mapped'          => false,
                'required'        => $options['required'] ?? true,
                'invalid_message' => _m('The password fields must match'),
            ],
        ];
    }

    /**
     * Create a form field for asking for an existing password
     *
     * @codeCoverageIgnore
     */
    public static function password(array $options = []): array
    {
        return [
            'password', PasswordType::class, [
                'label'       => _m('Password'),
                'label_attr'  => ['class' => 'section-form-label'],
                'attr'        => ['placeholder' => '********', 'autocomplete' => 'current-password'],
                'required'    => $options['required'] ?? true,
                'mapped'      => false,
                'constraints' => [
                    new NotBlank(['message' => _m('Please enter a password')]),
                    new Length(['min' => Common::config('password', 'min_length'), 'minMessage' => _m(['Your password should be at least # characters'], ['count' => Common::config('password', 'min_length')]),
                        'max'         => Common::config('password', 'max_length'), 'maxMessage' => _m(['Your password should be at most # characters'], ['count' => Common::config('password', 'max_length')]), ]),
                ], ],
        ];
    }

    /**
     * Create a from field for `select`ing a language for $actor, in reply or related to $context_actor
     */
    public static function language(?Actor $actor, ?Actor $context_actor, string $label, ?string $help = null, bool $multiple = false, bool $required = true, ?bool $use_short_display = null, ?string $form_id = null, bool $use_no_selection = false): array
    {
        [$language_choices, $preferred_language_choices] = Language::getSortedLanguageChoices($actor, $context_actor, use_short_display: $use_short_display);
        if ($use_no_selection) {
            $no_select = _m('(no selection)');
            // Put it at the beginning of $preferred_language_choices
            $preferred_language_choices = array_merge([$no_select => 'null'], $preferred_language_choices);
            // but at the top of $language_choices
            $language_choices[$no_select] = 'null';
        }
        return [
            $form_id ?? 'language' . ($multiple ? 's' : ''),
            ChoiceType::class,
            [
                'label'             => $label,
                'preferred_choices' => $preferred_language_choices,
                'choices'           => $language_choices,
                'required'          => $required,
                'multiple'          => $multiple,
                'help'              => $help,
            ],
        ];
    }
}
