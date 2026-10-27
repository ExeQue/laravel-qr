<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation;

use BackedEnum;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Contracts\ResolvesOptionRules;
use Illuminate\Validation\Rule;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

/**
 * The rules a class states with HasOptionRules, or else those its constructor's types imply:
 * a float is numeric, an int an integer, a bool a boolean, a backed enum one of its values.
 * A parameter of any other type cannot be given from input.
 */
final class OptionRulesResolver implements ResolvesOptionRules
{
    /**
     * @var array<class-string, array<string, mixed>>
     */
    private array $resolved = [];

    public function for(string $class): array
    {
        return $this->resolved[$class] ??= is_a($class, HasOptionRules::class, true)
            ? $class::optionRules()
            : $this->reflected($class);
    }

    /**
     * @param class-string $class
     *
     * @return array<string, mixed>
     */
    private function reflected(string $class): array
    {
        $rules = [];

        foreach (new ReflectionClass($class)->getConstructor()?->getParameters() ?? [] as $parameter) {
            $rule = $this->ruleOf($parameter);

            if ($rule !== null) {
                $rules[$parameter->getName()] = [$parameter->isDefaultValueAvailable() ? 'sometimes' : 'required', ...$rule];
            }
        }

        return $rules;
    }

    /**
     * @return list<mixed>|null
     */
    private function ruleOf(ReflectionParameter $parameter): ?array
    {
        $type = $parameter->getType();
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
        $names = array_map(fn (mixed $type): string => $type instanceof ReflectionNamedType ? $type->getName() : '', $types);
        $nullable = $type?->allowsNull() === true ? ['nullable'] : [];
        $enum = array_values(array_filter($names, fn (string $name): bool => is_subclass_of($name, BackedEnum::class)))[0] ?? null;

        return match (true) {
            $enum !== null => [...$nullable, Rule::enum($enum)],
            in_array('float', $names, true) => [...$nullable, 'numeric'],
            in_array('int', $names, true) => [...$nullable, 'integer'],
            $names === ['bool'] => ['boolean'],
            default => null,
        };
    }
}
