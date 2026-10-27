<?php

declare(strict_types=1);

namespace Tests\Feature\Rendering;

use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Facades\Qr;
use ExeQue\Qr\Filters\EyeTilt;
use ExeQue\Qr\Filters\MixedSize;
use ExeQue\Qr\Rendering\Enums\Severity;
use ExeQue\Qr\Rendering\Enums\Violation;
use ExeQue\Qr\Rendering\ScanSafety;
use ExeQue\Qr\Rendering\ScanSafetyReport;

covers(ScanSafetyReport::class, Severity::class, PendingQr::class, ScanSafety::class);

pest()->group('rendering');

const REPORT_URL = 'https://example.com/e/ABCD1234';

describe('the scan safety report', function (): void {
    it('is fine when the guards have nothing to say', function (): void {
        $report = Qr::url(REPORT_URL)->scanSafetyReport();

        expect($report->isFine())->toBeTrue()
            ->and($report->severity())->toBe(Severity::Fine)
            ->and($report->messages())->toBe([]);
    });

    it('tells adjustments from warnings, and what would be refused, without throwing', function (): void {
        $adjusted = Qr::url(REPORT_URL)->filter(new MixedSize(min: 0.2, max: 0.3))->scanSafetyReport();
        $warned = Qr::url(REPORT_URL)->paint('#ffffff')->background('#111111')->scanSafetyReport();
        $refused = Qr::url(REPORT_URL)->paint('#bbbbbb')->scanSafetyReport();
        $warnedContrast = Qr::url(REPORT_URL)->paint('#bbbbbb')->scanSafety(new ScanSafety(onViolation: Violation::Warn))->scanSafetyReport();

        expect($adjusted->severity())->toBe(Severity::Adjusted)
            ->and($warned->severity())->toBe(Severity::Warning)
            ->and($warned->messages())->toBe(['inverted' => 'lighter than its background, which not every reader scans'])
            ->and($refused->severity())->toBe(Severity::Refused)
            ->and($warnedContrast->severity())->toBe(Severity::Warning)
            ->and(Qr::url(REPORT_URL)->paint('#ffffff')->background('#111111')->scanSafety(new ScanSafety(inverted: Violation::Throw))->scanSafetyReport()->severity())->toBe(Severity::Refused);
    });

    it('says the worst first only once per guard, in the application\'s language', function (): void {
        $report = Qr::url(REPORT_URL)->margin(0)->filter(new MixedSize(min: 0.2, max: 0.3))->filter(new EyeTilt(outer: 30))->scanSafetyReport();
        app()->setLocale('da');

        expect(array_keys($report->messages()))->toBe(['margin', 'eye-rotation', 'scale'])
            ->and($report->messages()['margin'])->toBe('hævet fra 0 til 2 moduler')
            ->and($report->messages('en')['margin'])->toBe('raised from 0 to 2 modules')
            ->and($report->severity())->toBe(Severity::Warning)
            ->and(Severity::Refused->rank())->toBeGreaterThan(Severity::Warning->rank());
    });
});
