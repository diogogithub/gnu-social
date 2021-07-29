<?php

namespace App\Util;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

abstract class FormFields
{
    public static function password()
    {
        return ['password', RepeatedType::class,
            ['type'             => PasswordType::class,
                'first_options' => [
                    'label'       => _m('Password'),
                    'constraints' => [
                        new NotBlank(['message' => _m('Please enter a password')]),
                        new Length(['min' => Common::config('password', 'min_length'), 'minMessage' => _m(['Your password should be at least # characters'], ['count' => Common::config('password', 'min_length')]),
                            'max'         => Common::config('password', 'max_length'), 'maxMessage' => _m(['Your password should be at most # characters'],  ['count' => Common::config('password', 'max_length')]), ]),
                    ],
                ],
                'second_options' => [
                    'label' => _m('Repeat Password'),
                ],
                'mapped'          => false,
                'invalid_message' => _m('The password fields must match'),
            ],
        ];
    }
}
