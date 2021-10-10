<?php

declare(strict_types = 1);

namespace App\PHPStan;

use App\Core\DB\DB;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;

class ClassFromTableNameDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    private ?GNUsocialProvider $provider = null;
    public function __construct(GNUsocialProvider $provider)
    {
        $this->provider = $provider;
    }

    public function getClass(): string
    {
        return \App\Core\DB\DB::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array($methodReflection->getName(), DB::METHODS_ACCEPTING_TABLE_NAME);
    }

    /**
     * For calls to DB::find and such, if the first argument is a
     * constant string, it's a table name, so convert it to the
     * corresponding entity. Only run if the environment variable
     * PHPSTAN_BOOT_KERNEL is defined
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $staticCall,
        Scope $scope,
    ): \PHPStan\Type\Type {
        if (isset($_ENV['PHPSTAN_BOOT_KERNEL']) && \count($staticCall->args) >= 1 && ($arg = $staticCall->args[0]->value) instanceof String_) {
            // If called with the first argument as a string, it's a table name
            return $scope->resolveTypeByName(new Name(DB::filterTableName($staticCall->name->toString(), [$arg->value])));
        } else {
            // Let PHPStan handle it normally
            return \PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs($scope, $staticCall->args, $methodReflection->getVariants())->getReturnType();
        }
    }
}
