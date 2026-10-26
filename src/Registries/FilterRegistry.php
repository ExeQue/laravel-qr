<?php

declare(strict_types=1);

namespace ExeQue\Qr\Registries;

use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Exceptions\RegistryException;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Contracts\ProvidesQrFilter;
use ExeQue\Qr\Filters\Enums\Filter;
use ExeQue\Qr\Registries\Concerns\IsRegistry;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Support\Hydrate;

/**
 * Filters by name.
 */
final class FilterRegistry
{
    /** @use IsRegistry<NamedFilter> */
    use IsRegistry;

    public function kind(): string
    {
        return 'filter';
    }

    protected function contract(): string
    {
        return NamedFilter::class;
    }

    protected function provide(object $value): ?NamedFilter
    {
        return $value instanceof ProvidesQrFilter ? $value->qrFilter() : null;
    }

    protected function builtIn(): array
    {
        return array_column(array_map(fn (Filter $case): array => [$case->value, $case->class()], Filter::cases()), 1, 0);
    }

    /**
     * A filter from its name and options, as a stored style holds it: options
     * that limit where it applies (`only`, `in`, `corners`) are applied after it is made.
     *
     * @param array<string, mixed> $data `['name' => ..., 'options' => [...]]`
     */
    public function hydrate(array $data): NamedFilter
    {
        $options = (array) ($data['options'] ?? []);
        $scope = array_intersect_key($options, array_flip(['only', 'in', 'corners']));

        $filter = $this->get((string) ($data['name'] ?? ''), array_diff_key($options, $scope));

        if ($scope !== [] && ! method_exists($filter, 'only')) {
            throw RegistryException::notScopable($filter->name());
        }

        return match (true) {
            $scope === [] => $filter,
            default => $filter
                ->only(...array_map(Role::from(...), (array) ($scope['only'] ?? [])))
                ->in(...(isset($scope['in']) ? [Hydrate::region((array) $scope['in'])] : []))
                ->corners(...array_map(Corner::from(...), (array) ($scope['corners'] ?? []))),
        };
    }

    protected function configKey(): string
    {
        return 'filters';
    }
}
