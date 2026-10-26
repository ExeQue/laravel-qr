<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Exceptions\InvalidFilterException;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Support\Hydrate;

/**
 * Another filter, for cells of some roles only: timing or alignment cells drawn apart from data.
 */
final readonly class ByRole implements ModuleFilter, NamedFilter
{
    /**
     * @var list<Role>
     */
    private array $roles;

    private ModuleFilter&NamedFilter $filter;

    /**
     * @param list<Role|string> $roles
     * @param (ModuleFilter&NamedFilter)|array<string, mixed> $filter A filter, or its name and options.
     */
    public function __construct(array $roles, ModuleFilter|array $filter)
    {
        $this->roles = array_map(fn (Role|string $role): Role => $role instanceof Role ? $role : Role::from($role), $roles);
        $this->filter = self::moduleFilter($filter);
    }

    /**
     * @param ModuleFilter|array<string, mixed> $filter
     */
    private static function moduleFilter(ModuleFilter|array $filter): ModuleFilter&NamedFilter
    {
        $filter = is_array($filter) ? Hydrate::filter($filter) : $filter;

        return $filter instanceof ModuleFilter && $filter instanceof NamedFilter ? $filter : throw InvalidFilterException::notAModuleFilter();
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        return in_array($cell->role, $this->roles, true) ? $this->filter->module($cell, $style) : $style;
    }

    public function name(): string
    {
        return 'by-role';
    }

    public function options(): array
    {
        return [
            'roles' => array_map(fn (Role $role): string => $role->value, $this->roles),
            'filter' => ['name' => $this->filter->name(), 'options' => $this->filter->options()],
        ];
    }
}
