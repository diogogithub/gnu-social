<?php

namespace App\Util\Form;

use function App\Core\I18n\_m;
use App\Util\Common;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

abstract class FormFields
{
    public static function repeated_password(array $options = []): array
    {
        return ['password', RepeatedType::class,
            ['type'             => PasswordType::class,
                'first_options' => [
                    'label'      => _m('Password'),
                    'label_attr' => ['class' => 'section-form-label'],
                    'attr'       => ['placeholder' => '********'],
                    'required'   => $options['required'] ?? true,

                    'constraints' => [
                        new NotBlank(['message' => _m('Please enter a password')]),
                        new Length(['min' => Common::config('password', 'min_length'), 'minMessage' => _m(['Your password should be at least # characters'], ['count' => Common::config('password', 'min_length')]),
                            'max'         => Common::config('password', 'max_length'), 'maxMessage' => _m(['Your password should be at most # characters'],  ['count' => Common::config('password', 'max_length')]), ]),
                    ],
                    'help' => _m('Write a password with at least {min_length} characters, and a maximum of {max_length}.', ['min_length' => Common::config('password', 'min_length'), 'max_length' => Common::config('password', 'max_length')]),
                ],
                'second_options' => [
                    'label'      => _m('Repeat Password'),
                    'label_attr' => ['class' => 'section-form-label'],
                    'attr'       => ['placeholder' => '********'],
                    'help'       => _m('Confirm your password.'),
                ],
                'mapped'          => false,
                'invalid_message' => _m('The password fields must match'),
            ],
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public static function password(array $options = []): array
    {
        ['password', PasswordType::class, [
            'label'       => _m('Password'),
            'label_attr'  => ['class' => 'section-form-label'],
            'attr'        => ['placeholder' => '********'],
            'required'    => $options['required'] ?? true,
            'mapped'      => false,
            'constraints' => [
                new NotBlank(['message' => _m('Please enter a password')]),
                new Length(['min' => Common::config('password', 'min_length'), 'minMessage' => _m(['Your password should be at least # characters'], ['count' => Common::config('password', 'min_length')]),
                    'max'         => Common::config('password', 'max_length'), 'maxMessage' => _m(['Your password should be at most # characters'],  ['count' => Common::config('password', 'max_length')]), ]),
            ], ],
        ];
    }
}
