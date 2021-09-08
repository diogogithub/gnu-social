<?php

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
        return in_array($methodReflection->getName(), DB::METHODS_ACCEPTING_TABLE_NAME);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $staticCall,
        Scope $scope
    ): \PHPStan\Type\Type {
        if (count($staticCall->args) >= 1 && ($arg = $staticCall->args[0]->value) instanceof String_) {
            // If called with the first argument as a string, it's a table name
            return $scope->resolveTypeByName(new Name(DB::filterTableName($staticCall->name, [$arg->value])));
        } else {
            // Let PHPStan handle it normally
            return \PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs($scope, $staticCall->args, $methodReflection->getVariants())->getReturnType();
        }
    }
}
