# Artisan commands

## qr:check

```bash
php artisan qr:check
```

Builds everything your configuration names: every module shape, eye, preset, filter, gradient, writer, decoder and
style, the scan safety settings, your color names, the logo sanitizer, and a code with your defaults. Lists each one,
and every problem with what is wrong. Exits non-zero on any problem, so it fits CI and deploy scripts.

Parts that need options to be built (a palette needs its colors) are checked when they are used.

## qr:clear

```bash
php artisan qr:clear
```

Makes every cached code stale. See [Caching](caching.md).

## qr:install

```bash
php artisan qr:install
php artisan qr:install --package-manager=pnpm --svg --no-interaction
```

Installs `zxing-wasm` for the [zxing reader](reading.md#setting-up-zxing), with the package manager your lock file
names (`bun.lock`, `pnpm-lock.yaml`, `yarn.lock` or `package-lock.json`), or else the first of npm, pnpm, yarn and
bun that is installed. Asks which one to use, with that one chosen, and whether to read SVG too, which adds
`@resvg/resvg-wasm`. `--package-manager` and `--svg` answer without asking.

## qr:icons

```bash
php artisan qr:icons --path=resources/svg/qr
php artisan qr:icons --built-in --path=resources/svg/qr
```

Writes an SVG icon for every shape your application registers, into `modules/`, `outer-eyes/`, `inner-eyes/` and
`eyes/` under the path (`resources/svg/qr` by default). With `--built-in`, the package's own shapes instead, which
also ship ready-made. See [Shape icons](icons.md).
