<?php

declare(strict_types=1);

namespace Tests\Unit\Writers;

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
            expect($image->getImageProperty('Author'))->toBe('Acme & Co')
                ->and($image->getImageProperty('Copyright'))->toBe('© 2026 Acme');
        }
    })->with(ImageFormat::cases());

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
