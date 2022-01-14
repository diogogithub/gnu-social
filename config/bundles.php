<?php

declare(strict_types = 1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class                    => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class                              => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class                        => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class                => ['dev' => true, 'test' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class                        => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class                            => ['dev' => true, 'test' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class     => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class                     => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class         => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class                      => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class                            => ['dev' => true],
    SymfonyCasts\Bundle\VerifyEmail\SymfonyCastsVerifyEmailBundle::class     => ['all' => true],
    Misd\PhoneNumberBundle\MisdPhoneNumberBundle::class                      => ['all' => true],
    HtmlSanitizer\Bundle\HtmlSanitizerBundle::class                          => ['all' => true],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class             => ['dev' => true, 'test' => true],
    SymfonyCasts\Bundle\ResetPassword\SymfonyCastsResetPasswordBundle::class => ['all' => true],
    Knp\Bundle\TimeBundle\KnpTimeBundle::class                               => ['all' => true],
    Fidry\PsyshBundle\PsyshBundle::class                                     => ['all' => true],
    Trikoder\Bundle\OAuth2Bundle\TrikoderOAuth2Bundle::class                 => ['all' => true],
];
