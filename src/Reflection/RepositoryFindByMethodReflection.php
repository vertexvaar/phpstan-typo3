<?php declare(strict_types = 1);

namespace SaschaEgerer\PhpstanTypo3\Reflection;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class RepositoryFindByMethodReflection implements MethodReflection
{

	/** @var \PHPStan\Reflection\ClassReflection */
	private $classReflection;

	/** @var string */
	private $name;

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	public function __construct(ClassReflection $classReflection, string $name, Broker $broker)
	{
		$this->classReflection = $classReflection;
		$this->name = $name;
		$this->broker = $broker;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->classReflection;
	}

	public function isStatic(): bool
	{
		return false;
	}

	public function isPrivate(): bool
	{
		return false;
	}

	public function isPublic(): bool
	{
		return true;
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this;
	}

	public function getName(): string
	{
		return $this->name;
	}

	private function getPropertyName(): string
	{
		return lcfirst(substr($this->getName(), 6));
	}

	private function getModelName(): string
	{
		$className = $this->classReflection->getName();

		return ClassNamingUtility::translateRepositoryNameToModelName($className);
	}

	/**
	 * @return RepositoryFindByParameterReflection[]
	 */
	public function getParameters(): array
	{
		$modelReflection = $this->broker->getClass($this->getModelName());

		$type = $modelReflection->getNativeProperty($this->getPropertyName())->getReadableType();

		return [
			new RepositoryFindByParameterReflection('arg', $type),
		];
	}

	public function isVariadic(): bool
	{
		return false;
	}

	public function getReturnType(): Type
	{
		return new ObjectType(QueryResultInterface::class);
	}

	/**
	 * @return \PHPStan\Reflection\ParametersAcceptor[]
	 */
	public function getVariants(): array
	{
		return [
			new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				null,
				$this->getParameters(),
				$this->isVariadic(),
				$this->getReturnType()
			),
		];
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getThrowType(): ?\PHPStan\Type\Type
	{
		return null;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

}
