<?php

declare(strict_types=1);

namespace Tests\Unit\Writers;

use DOMDocument;
use ExeQue\Qr\Encoder\Encoder;
use ExeQue\Qr\Rendering\Renderer;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Writers\Metadata;
use ExeQue\Qr\Writers\Raster\Enums\ImageFormat;
use ExeQue\Qr\Writers\Raster\Imagick\Writer as RasterWriter;
use ExeQue\Qr\Writers\Raster\Options as RasterOptions;
use ExeQue\Qr\Writers\Svg\Options as SvgOptions;
use ExeQue\Qr\Writers\Svg\Writer as SvgWriter;
use Imagick;

covers(Metadata::class, RasterWriter::class, SvgWriter::class);

pest()->group('writers');

function attribution(): Metadata
{
    return Metadata::make(
        title: 'Wedding invite',
        author: 'Acme & Co',
        description: 'Scan for the program',
        copyright: '© 2026 Acme',
        url: 'https://acme.example/license',
        keywords: ['qr', 'invite'],
    );
}

describe('images', function (): void {
    it('writes XMP into every image format, and text chunks into PNG', function (ImageFormat $format): void {
        $scene = new Renderer()->render(new Encoder()->encode('hi'), Style::default());
        $image = new Imagick();
        $image->readImageBlob(new RasterWriter(new RasterOptions(100, $format, metadata: attribution()))->write($scene)->contents());

        $xmp = $image->getImageProfiles('xmp', true)['xmp'];

        expect($xmp)->toContain('<dc:creator><rdf:Seq><rdf:li>Acme &amp; Co</rdf:li></rdf:Seq></dc:creator>')
            ->and($xmp)->toContain('<xmpRights:WebStatement>https://acme.example/license</xmpRights:WebStatement>');

        if ($format === ImageFormat::Png) {
            // Plain text chunks hold Latin-1, which ImageMagick hands back as it is.
            expect($image->getImageProperty('Author'))->toBe('Acme & Co')
                ->and(mb_convert_encoding((string) $image->getImageProperty('Copyright'), 'UTF-8', 'ISO-8859-1'))->toBe('© 2026 Acme');
        }
    })->with(ImageFormat::cases());

    it('writes PNG fields as plain text, which Windows shows, and as international text beyond Latin-1, before the image data', function (string $driver): void {
        $png = \ExeQue\Qr\Facades\Qr::make('hi')->metadata(title: 'Kort', author: 'Zoë 東京', copyright: '© 2026 Acme')->png(100, driver: $driver)->contents();
        $chunks = [];

        for ($at = 8; $at < strlen($png); $at += 12 + unpack('N', substr($png, $at, 4))[1]) {
            $chunks[] = [substr($png, $at + 4, 4), substr($png, $at + 8, unpack('N', substr($png, $at, 4))[1])];
        }

        $types = array_column($chunks, 0);
        $text = fn (string $type, string $keyword): ?string => array_values(array_filter($chunks, fn (array $chunk): bool => $chunk[0] === $type && str_starts_with($chunk[1], "{$keyword}\0")))[0][1] ?? null;

        expect($text('tEXt', 'Title'))->toBe("Title\0Kort")
            ->and($text('tEXt', 'Copyright'))->toBe("Copyright\0\xA9 2026 Acme")
            ->and($text('iTXt', 'Author'))->toBe("Author\0\0\0\0\0Zoë 東京")
            ->and($text('iTXt', 'XML:com.adobe.xmp'))->not->toBeNull()
            ->and(array_search('tEXt', $types, true))->toBeLessThan(array_search('IDAT', $types, true))
            ->and(array_search('iTXt', $types, true))->toBeLessThan(array_search('IDAT', $types, true))
            ->and($png)->not->toContain('Raw profile type');
    })->with(['imagick', 'gd']);

    it('says why images cannot be written without Imagick', function (): void {
        expect(\ExeQue\Qr\Exceptions\MissingExtensionException::imagick()->getMessage())->toContain('needs the imagick PHP extension')
            ->and(\ExeQue\Qr\Exceptions\MissingExtensionException::gd()->getMessage())->toContain('needs the gd PHP extension')
            ->and(\ExeQue\Qr\Exceptions\MissingExtensionException::raster()->getMessage())->toContain('imagick or the gd PHP extension');
    })->covers(\ExeQue\Qr\Exceptions\MissingExtensionException::class);

});

describe('svg', function (): void {
    it('writes a title, a description and RDF into SVG', function (): void {
        $scene = new Renderer()->render(new Encoder()->encode('hi'), Style::default());

        expect(new SvgWriter(new SvgOptions(metadata: attribution()))->write($scene))->toMatchSnapshot();
    });
});

describe('values', function (): void {
    it('leaves out the control characters XML cannot hold', function (): void {
        $scene = new Renderer()->render(new Encoder()->encode('hi'), Style::default());
        $svg = new SvgWriter(new SvgOptions(metadata: Metadata::make(title: "A\x01B", description: "C\x1Fd\te")))->write($scene)->contents();

        expect(new DOMDocument()->loadXML($svg))->toBeTrue()
            ->and($svg)->toContain('<title>AB</title>');
    });

    it('writes nothing without metadata', function (): void {
        $scene = new Renderer()->render(new Encoder()->encode('hi'), Style::default());

        expect(new SvgWriter()->write($scene)->contents())->not->toContain('<metadata>');
    });

    it('lets a code\'s own values take the place of the defaults', function (): void {
        $defaults = Metadata::make(author: 'Acme', copyright: '© Acme', keywords: ['qr']);
        $merged = $defaults->merge(Metadata::make(title: 'Invite', author: 'Events team'));

        expect($merged)->toEqual(Metadata::make(title: 'Invite', author: 'Events team', copyright: '© Acme', keywords: ['qr']))
            ->and($defaults->merge(Metadata::make(keywords: ['invite']))->keywords)->toBe(['invite']);
    });

    it('knows when it is empty', function (): void {
        expect(new Metadata()->isEmpty())->toBeTrue()
            ->and(Metadata::make(url: 'https://acme.example')->isEmpty())->toBeFalse()
            ->and(Metadata::make(keywords: ['qr'])->isEmpty())->toBeFalse();
    });

    it('builds an XMP packet', function (): void {
        expect(attribution()->toXmp())->toMatchSnapshot();
    });
});
