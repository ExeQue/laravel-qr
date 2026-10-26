<?php

declare(strict_types=1);

namespace Tests\Feature\Decoding;

use ExeQue\Qr\Decoding\Contracts\Decoder;
use ExeQue\Qr\Decoding\Decoded;
use ExeQue\Qr\Decoding\NativeDecoder;
use ExeQue\Qr\Decoding\StackDecoder;
use ExeQue\Qr\Decoding\ZxingDecoder;
use ExeQue\Qr\Exceptions\DecoderUnavailableException;
use ExeQue\Qr\Exceptions\UnreadableCodeException;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Registries\DecoderRegistry;
use Illuminate\Support\Facades\Process;
use stdClass;

covers(\ExeQue\Qr\Qr::class, DecoderRegistry::class, StackDecoder::class, ZxingDecoder::class, DecoderUnavailableException::class, UnreadableCodeException::class, Decoded::class, \ExeQue\Qr\QrServiceProvider::class);

pest()->group('decoding');

const QR_READ_URL = 'https://example.com/e/ABCD1234';

beforeEach(function (): void {
    config()->set('qr.reading.zxing.node_modules', dirname(__DIR__, 3).'/node_modules');
});

describe('the decoder stack', function (): void {
    it('reads a rendered code with the stack, saying which decoder read it', function (): void {
        $decoded = Qr::read(Qr::make(QR_READ_URL)->png(300));

        expect($decoded->text)->toBe(QR_READ_URL)
            ->and($decoded->decoder)->toBe('zxing')
            ->and(Qr::read(Qr::make(QR_READ_URL)->png(300)->output(), 'native')->decoder)->toBe('native')
            ->and(Qr::decoders()->names())->toBe(['native', 'zxing', 'stack']);
    })->group('scan');

    it('passes on to the next decoder what one cannot read, or is not installed', function (): void {
        config()->set('qr.reading.zxing.node_modules', sys_get_temp_dir());
        $decoded = Qr::read(Qr::make(QR_READ_URL)->png(300));

        expect($decoded->text)->toBe(QR_READ_URL)
            ->and($decoded->decoder)->toBe('native');
    })->group('scan');

    it('says why every decoder failed', function (): void {
        config()->set('qr.reading.stack', ['native']);

        Qr::read('not an image');
    })->throws(UnreadableCodeException::class, 'No decoder could read the QR code: native: The image could not be read');

    it('takes decoders of an application\'s own', function (): void {
        Qr::decoders()->register('fixed', fn (): Decoder => new class () implements Decoder {
            public function decode(string $image): Decoded
            {
                return new Decoded('fixed: '.strlen($image));
            }
        });
        config()->set('qr.reading.decoder', 'fixed');

        expect(Qr::read('12345')->text)->toBe('fixed: 5')
            ->and(Qr::read('12345')->decoder)->toBe('fixed')
            ->and(Qr::decoders()->get('native'))->toBeInstanceOf(NativeDecoder::class)
            ->and(Qr::decoders()->kind())->toBe('decoder')
            ->and(function (): void {
                Qr::decoders()->register('broken', stdClass::class);
                Qr::decoders()->get('broken');
            })->toThrow(\ExeQue\Qr\Exceptions\RegistryException::class);
    });

});

describe('zxing', function (): void {
    it('reads with zxing through Node', function (): void {
        $decoded = Qr::read(Qr::make(QR_READ_URL)->png(300)->contents(), 'zxing');

        expect($decoded->text)->toBe(QR_READ_URL)
            ->and($decoded->version)->toBe(3)
            ->and(fn () => Qr::read('not an image', 'zxing'))->toThrow(UnreadableCodeException::class, 'No QR code');
    })->group('scan');

    it('says what zxing needs when it is missing', function (array $zxing, string $message): void {
        config()->set('qr.reading.zxing', [...config('qr.reading.zxing'), ...$zxing]);

        expect(fn () => Qr::read(Qr::make(QR_READ_URL)->png(100), 'zxing'))->toThrow(DecoderUnavailableException::class, $message);
    })->with([
        'no Node' => [['command' => ['no-such-node']], 'needs Node.js; "no-such-node"'],
        'no packages' => [['node_modules' => sys_get_temp_dir()], 'npm install zxing-wasm'],
    ])->group('scan');

    it('gives up on a reader that hangs', function (): void {
        config()->set('qr.reading.zxing', ['command' => ['sh', '-c', 'sleep 3', '--'], 'node_modules' => sys_get_temp_dir(), 'timeout' => 1]);

        expect(fn () => Qr::read(Qr::make(QR_READ_URL)->png(100), 'zxing'))->toThrow(DecoderUnavailableException::class, 'failed');
        config()->set('qr.reading.stack', ['zxing', 'native']);

        expect(Qr::read(Qr::make(QR_READ_URL)->png(300))->decoder)->toBe('native');
    });

    it('reads SVG with a byte order mark and a doctype', function (): void {
        $svg = "\u{FEFF}".'<?xml version="1.0"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'.Qr::make(QR_READ_URL)->svg()->contents();

        expect(Qr::read($svg, 'zxing')->text)->toBe(QR_READ_URL);
    })->group('scan');

    it('uses the application\'s node_modules unless told otherwise', function (): void {
        config()->set('qr.reading.zxing.node_modules', null);
        Process::fake(['*' => Process::result(json_encode(['text' => 'faked', 'version' => null]))]);

        expect(Qr::read('image', 'zxing')->text)->toBe('faked');
        Process::assertRan(fn ($process): bool => end($process->command) === base_path('node_modules'));
    });
});
