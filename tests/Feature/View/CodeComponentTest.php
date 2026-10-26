<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\View\Components\Code;
use Illuminate\Support\Facades\Blade;

covers(Code::class);

pest()->group('view');

const COMPONENT_URL = 'https://example.com/e/ABCD1234';

describe('the code component', function (): void {
    it('renders a code as inline SVG, labelled for screen readers', function (): void {
        $html = trim(Blade::render('<x-qr::code :content="$url" class="size-40" />', ['url' => COMPONENT_URL]));

        expect($html)->toStartWith('<svg role="img" aria-label="QR code" class="size-40" xmlns=')
            ->and($html)->not->toContain('<?xml')
            ->and(Qr::make(COMPONENT_URL)->svg()->contents())->toContain(substr($html, strpos($html, 'viewBox')));
    });

    it('answers to x-qr too, with a style, a label and the text color', function (): void {
        Qr::styles()->register('night', ['margin' => 2]);

        $html = trim(Blade::render('<x-qr :content="$url" style="night" label="Join us" current-color />', ['url' => COMPONENT_URL]));

        expect($html)->toContain('aria-label="Join us"')
            ->toContain('currentColor')
            ->toContain('viewBox="0 0 33 33"');
    });

    it('renders an image with a PNG data URI', function (): void {
        $html = trim(Blade::render('<x-qr::code :content="$url" as="img" :size="120" label="Ticket" class="w-32" />', ['url' => COMPONENT_URL]));

        expect($html)->toStartWith('<img src="data:image/png;base64,')
            ->toEndWith('alt="Ticket" class="w-32">');
        $png = base64_decode(str($html)->between('base64,', '"')->toString());
        expect(getimagesizefromstring($png))->toMatchArray([0 => 120, 1 => 120]);
    });

    it('escapes what it is given', function (): void {
        $html = trim(Blade::render('<x-qr::code :content="$url" :label="$evil" :data-x="$evil" />', ['url' => COMPONENT_URL, 'evil' => '"><script>']));

        expect($html)->not->toContain('<script>')->toContain('aria-label="&quot;&gt;&lt;script&gt;"');
    });

    it('writes attribute values as they are', function (): void {
        expect(trim(Blade::render('<x-qr::code :content="$url" label="Pay $0 now \\1" />', ['url' => COMPONENT_URL])))
            ->toContain('aria-label="Pay $0 now \\1"');
    });

});
