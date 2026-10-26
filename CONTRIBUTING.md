# Contributing

Issues and pull requests are welcome. Keep a change small and covered by tests: CI requires 100 % line coverage and checks the formatting.

## The workbench

### With lerd

[lerd](https://github.com/lerd-env/lerd) serves the workbench at `https://laravel-qr.test`.

```bash
composer install
```

### With Docker

```bash
docker compose run --rm composer install
docker compose up serve
```

Open `http://localhost:8000`.

## Checks

```bash
composer test           # the suite
composer test:coverage  # the suite, failing below 100 % coverage
composer lint           # format the code
composer lint:test      # check the formatting, as CI does
```

With Docker, run them through the composer service: `docker compose run --rm composer test`.

The documentation is built with VitePress:

```bash
npm install
npm run docs:dev
```
