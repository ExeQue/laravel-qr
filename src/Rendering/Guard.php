<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering;

use ExeQue\Qr\Colors\Contrast;
use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Role;
use ExeQue\Qr\Encoder\Matrix;
use ExeQue\Qr\Exceptions\ScanSafetyException;
use ExeQue\Qr\Logos\Knockout;
use ExeQue\Qr\Rendering\Enums\Violation;
use ExeQue\Qr\Support\Number;
use ExeQue\Qr\Support\Paints;

/**
 * Applies the scan safety limits to one render, keeping a record of what it changed.
 *
 * @internal
 */
final class Guard
{
    /**
     * @var list<GuardAdjustment>
     */
    private array $adjustments = [];

    private int $skipped = 0;

    /**
     * @var int Data cells the logo cleared: what the error correction already spends on it.
     */
    private int $cleared = 0;

    /**
     * @var array<string, array{float, float}> Drawn widths and heights, by shape and neighbors.
     */
    private array $extents = [];

    /**
     * @param int $dataCells All the symbol's data cells, dark and light: what the skip share is of.
     */
    public function __construct(
        private readonly ScanSafety $safety,
        private readonly ErrorCorrection $errorCorrection,
        private readonly int $dataCells,
    ) {
    }

    public function margin(int $margin): int
    {
        $min = $this->safety->minMargin;

        if ($min === null || $margin >= $min) {
            return $margin;
        }

        $this->adjustments[] = new GuardAdjustment('margin', "raised from {$margin} to {$min} modules");

        return $min;
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        $where = [$cell->column, $cell->row];

        if ($this->safety->maxOffset !== null) {
            $max = $this->safety->maxOffset;
            [$x, $y] = [max(-$max, min($max, $style->offsetX)), max(-$max, min($max, $style->offsetY))];

            if ([$x, $y] !== [$style->offsetX, $style->offsetY]) {
                $this->adjustments[] = new GuardAdjustment('offset', 'held to '.Number::format($max).' modules', $where);
                $style = $style->withOffset($x, $y);
            }
        }

        if ($this->safety->minScale !== null || $this->safety->maxScale !== null) {
            $style = $this->moduleScale($cell, $style, $where);
        }

        if ($this->safety->maxSkew !== null) {
            $max = $this->safety->maxSkew;
            [$x, $y] = [max(-$max, min($max, $style->skewX)), max(-$max, min($max, $style->skewY))];

            if ([$x, $y] !== [$style->skewX, $style->skewY]) {
                $this->adjustments[] = new GuardAdjustment('skew', 'held to '.Number::format($max).' degrees', $where);
                $style = $style->withSkew($x, $y);
            }
        }

        if ($style->skip && ! $this->maySkip($cell)) {
            $this->adjustments[] = new GuardAdjustment('skip', 'drawn after all', $where);
            $style = $style->withSkip(false);
        }

        return $style;
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        $style = $style
            ->withOuter($this->eyeScale($eye, $style->outer, $this->safety->outerEyeScale, 'outer-eye-scale'))
            ->withInner($this->eyeScale($eye, $style->inner, $this->safety->innerEyeScale, 'inner-eye-scale'));

        foreach (['outer' => $style->outer, 'inner' => $style->inner] as $part => $partStyle) {
            // A quarter turn keeps the finder pattern as it was; anything between distorts it.
            $offQuarter = abs($partStyle->rotation - round($partStyle->rotation / 90) * 90);

            if ($this->safety->eyeRotationWarning !== null && $offQuarter > $this->safety->eyeRotationWarning) {
                $this->adjustments[] = new GuardAdjustment('eye-rotation', "{$part} eye turned ".Number::format($offQuarter).' degrees off a quarter turn', corner: $eye->corner, warning: true);
            }
        }

        return $style;
    }

    /**
     * A logo may clear only so much of what the error correction can restore.
     */
    public function logo(Knockout $knockout, Matrix $matrix): void
    {
        $this->cleared = $knockout->clearedDataCells;

        if ($this->safety->maxLogoShare === null) {
            return;
        }

        $budget = Knockout::budget($matrix, $this->safety->maxLogoShare);

        if ($knockout->clearedDataCells <= $budget) {
            return;
        }

        if ($this->safety->onViolation === Violation::Throw) {
            throw ScanSafetyException::logoTooLarge($knockout->clearedDataCells, $budget);
        }

        $this->adjustments[] = new GuardAdjustment('logo', "clears {$knockout->clearedDataCells} data cells, more than the {$budget} it may", warning: true);
    }

    /**
     * Checks every drawn paint against the background.
     *
     * @param list<SceneItem> $items
     */
    public function contrast(array $items, ?Paint $background): void
    {
        if ($this->safety->minContrast === null) {
            return;
        }

        $grounds = $background === null ? [new Hex('#ffffff')] : Paints::colors($background);
        $worst = INF;
        $checked = [];

        foreach ($items as $item) {
            // Most items share a paint: each paint and opacity is measured once.
            $key = spl_object_id($item->paint).':'.$item->opacity;

            if (isset($checked[$key])) {
                continue;
            }

            $checked[$key] = true;

            foreach (Paints::colors($item->paint) as $color) {
                foreach ($grounds as $ground) {
                    $worst = min($worst, Contrast::ratio($color, $ground, $item->opacity));
                }
            }
        }

        if ($worst >= $this->safety->minContrast) {
            return;
        }

        if ($this->safety->onViolation === Violation::Throw) {
            throw ScanSafetyException::lowContrast($worst, $this->safety->minContrast);
        }

        $this->adjustments[] = new GuardAdjustment('contrast', 'only '.Number::format($worst, 2).':1 against the background', warning: true);
    }

    /**
     * @return list<GuardAdjustment>
     */
    public function adjustments(): array
    {
        return $this->adjustments;
    }

    /**
     * The module's size held between the limits, its shape's own size counted: a dot of a tenth
     * of a module is as small as a square scaled to a tenth.
     *
     * @param array{int, int} $where
     */
    private function moduleScale(ModuleContext $cell, ModuleStyle $style, array $where): ModuleStyle
    {
        [$width, $height] = $this->extent($cell, $style);
        [$least, $most] = [$this->safety->minScale ?? 0.0, $this->safety->maxScale ?? INF];

        // The scale that keeps what is drawn within the limits, each way.
        $x = max($least / $width, min($most, $style->scaleX));
        $y = max($least / $height, min($most, $style->scaleY));

        if ([$x, $y] === [$style->scaleX, $style->scaleY]) {
            return $style;
        }

        $this->adjustments[] = new GuardAdjustment('scale', 'held to '.Number::format(min($x * $width, $y * $height)).' of a module', $where);

        return $style->withSize($x, $y);
    }

    /**
     * How wide and tall the module's shape is drawn. A shape draws by its neighbors at most, so
     * one measure serves every module with the same shape and neighbors.
     *
     * @return array{float, float}
     */
    private function extent(ModuleContext $cell, ModuleStyle $style): array
    {
        $neighbors = '';

        foreach ([-1, 0, 1] as $dy) {
            foreach ([-1, 0, 1] as $dx) {
                $neighbors .= $cell->isDark($dx, $dy) ? '1' : '0';
            }
        }

        $key = spl_object_id($style->shape).':'.$neighbors;

        if (! isset($this->extents[$key])) {
            [$left, $top, $right, $bottom] = $style->shape->draw($cell)->bounds() ?? [-0.5, -0.5, 0.5, 0.5];
            $this->extents[$key] = [max(0.01, min(1.0, $right - $left)), max(0.01, min(1.0, $bottom - $top))];
        }

        return $this->extents[$key];
    }

    /**
     * Function patterns are never left out, and data only within what error correction can still
     * restore once the logo is paid for. A module left out may break a whole codeword of eight.
     */
    private function maySkip(ModuleContext $cell): bool
    {
        if ($this->safety->maxSkip === null) {
            return true;
        }

        $restorable = $this->errorCorrection->recoveryRatio() * $this->dataCells / 8;
        $budget = (int) floor($this->safety->maxSkip * max(0.0, $restorable - $this->cleared / 8));

        if ($cell->role !== Role::Data || $this->skipped >= $budget) {
            return false;
        }

        $this->skipped++;

        return true;
    }

    /**
     * @param array{float, float}|null $range
     */
    private function eyeScale(EyeContext $eye, EyePartStyle $part, ?array $range, string $guard): EyePartStyle
    {
        if ($range === null) {
            return $part;
        }

        [$x, $y] = [max($range[0], min($range[1], $part->scaleX)), max($range[0], min($range[1], $part->scaleY))];

        if ([$x, $y] === [$part->scaleX, $part->scaleY]) {
            return $part;
        }

        $this->adjustments[] = new GuardAdjustment($guard, 'held to '.Number::format($range[0]).'-'.Number::format($range[1]), corner: $eye->corner);

        return $part->withSize($x, $y);
    }
}
