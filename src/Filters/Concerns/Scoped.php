<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters\Concerns;

use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Filters\Region;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\ModuleContext;

/**
 * Limits a filter to some cells or corners. Each returns a changed copy.
 *
 * @internal
 */
trait Scoped
{
    /**
     * @var list<Role>
     */
    private array $roles = [];

    private ?Region $region = null;

    /**
     * @var list<Corner>
     */
    private array $corners = [];

    /**
     * Only cells with these roles.
     */
    public function only(Role ...$roles): static
    {
        return clone($this, ['roles' => array_values($roles)]);
    }

    /**
     * Only cells inside the region.
     */
    public function in(?Region $region = null): static
    {
        return clone($this, ['region' => $region]);
    }

    /**
     * Only the eyes in these corners.
     */
    public function corners(Corner ...$corners): static
    {
        return clone($this, ['corners' => array_values($corners)]);
    }

    private function appliesTo(ModuleContext $cell): bool
    {
        return ($this->roles === [] || in_array($cell->role, $this->roles, true))
            && ($this->region === null || $this->region->containsCell($cell));
    }

    private function appliesToEye(EyeContext $eye): bool
    {
        return $this->corners === [] || in_array($eye->corner, $this->corners, true);
    }

    /**
     * The scope, for `options()`; nothing when unscoped.
     *
     * @return array<string, mixed>
     */
    private function scope(): array
    {
        return array_filter([
            'only' => array_map(fn (Role $role): string => $role->value, $this->roles),
            'in' => $this->region?->toArray(),
            'corners' => array_map(fn (Corner $corner): string => $corner->value, $this->corners),
        ], fn (mixed $value): bool => $value !== [] && $value !== null);
    }
}
