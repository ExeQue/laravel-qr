<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use ExeQue\Qr\Builders\Rendered;
use ExeQue\Qr\Facades\Qr;
use Workbench\App\Http\Requests\RenderCodeRequest;

/**
 * Renders a code from a request: `POST /api/codes` with `content`, `format`, `size` and `style`.
 */
class RenderCodeController
{
    public function __invoke(RenderCodeRequest $request): Rendered
    {
        $code = Qr::make($request->string('content')->toString())->style($request->qrStyle());
        $size = $request->integer('size', 600);

        return match ($request->string('format', 'svg')->toString()) {
            'png' => $code->png($size),
            'jpeg' => $code->jpeg($size),
            'webp' => $code->webp($size),
            default => $code->svg(),
        };
    }
}
