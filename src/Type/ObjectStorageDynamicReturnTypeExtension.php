<?php declare(strict_types = 1);

namespace SaschaEgerer\PhpstanTypo3\Type;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

use function substr;

/**
 * Add support for ObjectStorage methods "current" and "next".
 * The target type is fetched by getting the propertyName, calculated by the getter,
 * and then getting the property annotation of the class.
 * This one could be very unstable but works for "my" current usecase.
 */
class ObjectStorageDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return ObjectStorage::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection
	): bool
	{
		return in_array($methodReflection->getName(), ['current', 'offsetGet'], true);
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type
	{
		if ($methodCall->var instanceof Variable && is_string($methodCall->var->name)
			&& in_array(substr($methodCall->var->name, 0, 3), ['set', 'get'])
		) {
			$propertyName = lcfirst(substr($methodCall->var->name, 3));

			$class = $scope->getClassReflection();
			if ($class instanceof ClassReflection && $class->hasProperty($propertyName)) {
				preg_match(
					'/@var\\ \\\\TYPO3\\\\CMS\\\\Extbase\\\\Persistence\\\\ObjectStorage<(.*)>/',
					(string) $class->getNativeReflection()->getProperty($propertyName)->getDocComment(),
					$phpDocVarAnnotations
				);
				if (isset($phpDocVarAnnotations[1])) {
					return TypeCombinator::addNull(new ObjectType($phpDocVarAnnotations[1]));
				}
			}
		}

		return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
	}

}
