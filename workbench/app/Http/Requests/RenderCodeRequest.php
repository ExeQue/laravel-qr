<?php

declare(strict_types=1);

namespace Workbench\App\Http\Requests;

use ExeQue\Qr\Validation\InteractsWithQrStyle;
use ExeQue\Qr\Validation\StyleRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A code to render: its content, a format and size, and a style checked field by field.
 */
class RenderCodeRequest extends FormRequest
{
    use InteractsWithQrStyle;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'format' => ['sometimes', 'in:svg,png,jpeg,webp'],
            'size' => ['sometimes', 'integer', 'between:64,2048'],
            ...StyleRule::defaults()->for('style'),
        ];
    }
}
