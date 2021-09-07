<?php

namespace App\PHPStan;

use App\Core\DB\DB;
use App\Util\Formatting;
use Doctrine\Persistence\ObjectManager;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Constant\ConstantStringType;

class ClassFromTableNameDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    private ?ObjectManager $manager = null;
    public function __construct(ObjectManagerResolver $resolver)
    {
        $this->manager = $resolver->getManager();
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
    ): \PHPStan\Type\Type
    {
        if (count($staticCall->args) === 0) {
            return \PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs($scope, $staticCall->args, $methodReflection->getVariants())->getReturnType();
        }
        $arg = $staticCall->args[0]->value;
        if ($arg instanceof String_) {
            DB::setManager($this->manager);
            DB::initTableMap();
            $class = DB::filterTableName($staticCall->name, [$arg->value]);
            return $scope->resolveTypeByName(new Name($class));
        } else {
            return \PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs($scope, $staticCall->args, $methodReflection->getVariants())->getReturnType();
        }
    }
}
