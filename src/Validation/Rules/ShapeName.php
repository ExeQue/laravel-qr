<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Closure;
use ExeQue\Qr\Registries\InnerEyeRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Registries\OuterEyeRegistry;
use ExeQue\Qr\Validation\Allow;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The registered name of a module, outer eye or inner eye shape that may be chosen.
 *
 * @internal
 */
final class ShapeName implements ValidationRule
{
    /**
     * Run on empty strings too, which Laravel otherwise passes.
     */
    public bool $implicit = true;

    /**
     * @param 'module'|'outer'|'inner' $kind
     */
    public function __construct(
        private readonly string $kind,
        private readonly Allow $allow,
    ) {
    }

    /**
     * @param 'module'|'outer'|'inner' $kind
     */
    public static function registry(string $kind): ModuleRegistry|OuterEyeRegistry|InnerEyeRegistry
    {
        return app(match ($kind) {
            'module' => ModuleRegistry::class,
            'outer' => OuterEyeRegistry::class,
            'inner' => InnerEyeRegistry::class,
        });
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // A missing shape is for required_with to judge.
        if ($value !== null && (! is_string($value) || ! self::registry($this->kind)->has($value) || ! $this->allow->permits($value))) {
            $fail(trans("qr::validation.shape.{$this->kind}"));
        }
    }
}
