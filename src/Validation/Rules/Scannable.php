<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Closure;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Rendering\Enums\Violation;
use ExeQue\Qr\Rendering\GuardAdjustment;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Validation\StyleInput;
use ExeQue\Qr\Validation\StyleRule;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * Draws the style with the scan guards the code is drawn with, and turns whatever a guard
 * would throw for or change into an error on the field it comes from. Silent while the
 * style has other errors, and with the guards off.
 *
 * @internal
 */
final readonly class Scannable implements ValidationRule
{
    public function __construct(
        private StyleRule $rules,
        private ?ScanSafety $safety,
        private string $content,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || app(Factory::class)->make([$attribute => $value], $this->rules->for($attribute))->fails()) {
            return;
        }

        $safety = $this->safety ?? ScanSafety::fromConfig((array) config('qr.scan_safety', []));
        $safety = new ScanSafety(...[...get_object_vars($safety), 'onViolation' => Violation::Warn]);

        try {
            // A stand-in logo: the guards weigh only how much of the code it clears.
            $logo = Logo::fromSvg('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><rect width="1" height="1"/></svg>');
            $style = StyleInput::apply($value, Style::default(), $logo);
            $default = config('qr.defaults.error_correction', ErrorCorrection::Medium);
            $level = match (true) {
                $style->logo !== null => ErrorCorrection::High,
                $default instanceof ErrorCorrection => $default,
                default => ErrorCorrection::from((string) $default),
            };
            $adjustments = app(Renderer::class)->render(app(Encoder::class)->encode($this->content, $level), $style, $safety)->adjustments;
        } catch (Throwable $exception) {
            $fail($exception->getMessage());

            return;
        }

        $reported = [];

        foreach ($adjustments as $adjustment) {
            $field = $this->field($adjustment, $value);

            if (! isset($reported[$adjustment->guard])) {
                $reported[$adjustment->guard] = true;
                $fail($field === null ? $attribute : "{$attribute}.{$field}", trans('qr::validation.scannable.'.$adjustment->guard, ['detail' => $adjustment->detail]));
            }
        }
    }

    /**
     * The field a guard's change comes from, as far as the input tells.
     *
     * @param array<array-key, mixed> $input
     */
    private function field(GuardAdjustment $adjustment, array $input): ?string
    {
        return match ($adjustment->guard) {
            'contrast' => match (true) {
                array_key_exists('background', $input) => 'background',
                isset($input['modules']['paint']) => 'modules.paint',
                default => null,
            },
            'logo' => 'logo.size',
            'margin' => 'margin',
            'offset', 'scale', 'skew', 'skip' => isset($input['filters']) ? 'filters' : 'modules',
            default => 'eyes',
        };
    }
}
