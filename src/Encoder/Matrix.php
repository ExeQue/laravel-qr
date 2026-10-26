<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder;

use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use ExeQue\Qr\Encoder\Enums\Mask;
use ExeQue\Qr\Encoder\Enums\Role;

/**
 * A finished symbol: which cells are dark, and what each cell is for.
 * Columns are `x`, rows are `y`, both from the top-left corner, without the quiet zone.
 */
final readonly class Matrix
{
    private const array SNAPSHOT_ROLES = [
        'finder' => 'F',
        'separator' => 's',
        'timing' => 't',
        'alignment' => 'a',
        'format' => 'f',
        'version' => 'v',
        'dark-module' => 'd',
        'data' => '.',
    ];

    /**
     * @var positive-int
     */
    public int $size;

    /**
     * @internal Built by the encoder.
     *
     * @param int<1, 40> $version
     * @param array<int, bool> $dark Row by row.
     * @param array<int, Role> $roles Row by row.
     */
    public function __construct(
        public int $version,
        public ErrorCorrection $errorCorrection,
        public Mask $mask,
        private array $dark,
        private array $roles,
    ) {
        $this->size = 17 + 4 * $version;
    }

    /**
     * Whether the cell is dark; cells outside the symbol are light, as the quiet zone is.
     */
    public function isDark(int $x, int $y): bool
    {
        return $this->contains($x, $y) && $this->dark[$y * $this->size + $x];
    }

    /**
     * The cell's role, or null outside the symbol.
     */
    public function role(int $x, int $y): ?Role
    {
        return $this->contains($x, $y) ? $this->roles[$y * $this->size + $x] : null;
    }

    /**
     * The grid as text for snapshots: `#` dark and `.` light, then the roles.
     */
    public function toSnapshot(): string
    {
        $legend = implode(', ', array_map(
            fn (string $role, string $letter): string => "{$letter} {$role}",
            array_keys(self::SNAPSHOT_ROLES),
            self::SNAPSHOT_ROLES,
        ));

        $cells = array_chunk(array_map(fn (bool $dark): string => $dark ? '#' : '.', $this->dark), $this->size);
        $roles = array_chunk(array_map(fn (Role $role): string => self::SNAPSHOT_ROLES[$role->value], $this->roles), $this->size);

        return implode("\n", [
            "version {$this->version}, error correction {$this->errorCorrection->value}, mask {$this->mask->value}, size {$this->size}",
            '',
            ...array_map(implode(...), $cells),
            '',
            "roles: {$legend}",
            '',
            ...array_map(implode(...), $roles),
        ])."\n";
    }

    private function contains(int $x, int $y): bool
    {
        return $x >= 0 && $y >= 0 && $x < $this->size && $y < $this->size;
    }
}
