@props(['title' => null])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "{$title} - " : '' }}laravel-qr</title>
    <style>
        :root {
            --accent: #be123c;
            --text: #1c1917;
            --muted: #57534e;
            --line: #e7e5e4;
            --surface: #ffffff;
            --background: #f5f5f4;
            --danger: #b42318;
            --danger-soft: #fdeceb;
            --warning-soft: #fef6e7;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font: 15px/1.5 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--text);
            background: var(--background);
        }

        header { background: var(--surface); border-bottom: 1px solid var(--line); padding: 14px 24px; }
        header strong { font-size: 17px; }
        header span { color: var(--muted); margin-left: 8px; }

        main { padding: 24px; }

        .playground { display: grid; grid-template-columns: minmax(300px, 380px) 1fr; gap: 24px; align-items: start; }
        @media (max-width: 860px) { .playground { grid-template-columns: 1fr; } }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 16px; }
        .controls { display: grid; gap: 14px; max-height: calc(100vh - 120px); overflow: auto; }

        fieldset { border: 0; margin: 0; padding: 0; display: grid; gap: 8px; }
        legend { font-weight: 600; margin-bottom: 4px; }
        label { display: grid; gap: 4px; color: var(--muted); font-size: 13px; }
        label.inline { display: flex; align-items: center; gap: 8px; color: var(--text); font-size: 14px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

        input[type=text], input[type=number], select, textarea {
            width: 100%; padding: 7px 9px; border: 1px solid var(--line); border-radius: 6px; font: inherit; background: #fff;
        }
        input[type=color] { width: 100%; height: 34px; border: 1px solid var(--line); border-radius: 6px; padding: 2px; background: #fff; }
        input[type=range] { width: 100%; }

        .filter { border-top: 1px solid var(--line); padding-top: 8px; display: grid; gap: 6px; }
        .filter-head { display: flex; justify-content: space-between; align-items: center; }
        .filter-head button, button { border: 1px solid var(--line); background: #fff; border-radius: 6px; padding: 4px 9px; font: inherit; cursor: pointer; }
        button:hover { border-color: var(--accent); color: var(--accent); }
        details { border-top: 1px solid var(--line); padding-top: 10px; display: grid; gap: 8px; }
        details[open] { padding-bottom: 6px; }
        summary { font-weight: 600; cursor: pointer; margin-bottom: 6px; }
        details > * + * { margin-top: 8px; }
        h4 { margin: 8px 0 0; font-size: 13px; }
        .hint { margin: 0; color: var(--muted); font-size: 12px; }
        .paint { display: grid; gap: 8px; }
        .chips { display: flex; flex-wrap: wrap; gap: 4px 12px; }
        .chips label.inline { font-size: 12px; }
        .row.three { grid-template-columns: 1fr 1fr 1fr; }
        .row.four { grid-template-columns: repeat(4, 1fr); }
        .stats .good { color: #166534; font-weight: 600; }
        .stats .bad { color: var(--danger); font-weight: 600; }

        .stage { display: grid; gap: 14px; justify-items: center; }
        .code { width: min(100%, 560px); aspect-ratio: 1; display: grid; place-items: center;
            background: repeating-conic-gradient(#f0efee 0% 25%, #fafaf9 0% 50%) 50% / 20px 20px; border-radius: 10px; }
        .code svg, .code img { width: 100%; height: 100%; }

        .stats { display: flex; flex-wrap: wrap; gap: 8px 18px; color: var(--muted); font-size: 13px; }
        .stats b { color: var(--text); }

        .notice { padding: 10px 12px; border-radius: 8px; font-size: 14px; width: min(100%, 560px); }
        .notice.error { background: var(--danger-soft); color: var(--danger); }
        .notice.warning { background: var(--warning-soft); }
        .notice ul { margin: 4px 0 0; padding-left: 18px; }

        .layer { display: grid; gap: 8px; border: 1px dashed var(--line); border-radius: 8px; padding: 8px; }
        .field { color: var(--muted); font-size: 13px; }
        .downloads { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; color: var(--muted); font-size: 13px; }
        .shapes { display: grid; grid-template-columns: repeat(auto-fill, minmax(42px, 1fr)); gap: 6px; }
        .shape { display: grid; place-items: center; aspect-ratio: 1; border: 1px solid var(--line); border-radius: 8px; padding: 6px; cursor: pointer; color: inherit; position: relative; }
        .shape:hover { border-color: var(--muted); }
        .shape:has(input:checked) { border-color: var(--accent); color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
        .shape:has(input:focus-visible) { outline: 2px solid var(--accent); outline-offset: 2px; }
        .shape input { position: absolute; opacity: 0; pointer-events: none; }
        .shape svg { width: 100%; height: 100%; display: block; }
        .shape-blank { font-size: 11px; text-align: center; line-height: 1.1; }
        .layer > summary { display: flex; justify-content: space-between; align-items: center; margin: 0; }
        .layer > summary .hint { font-weight: 400; margin-left: 6px; }
        label.range > span { display: flex; justify-content: space-between; }
        label.range output { color: var(--text); font-variant-numeric: tabular-nums; }
        input.handle-only { -webkit-appearance: none; appearance: none; background: transparent; height: 20px; }
        input.handle-only::-webkit-slider-runnable-track { background: transparent; height: 20px; }
        input.handle-only::-moz-range-track { background: transparent; }
        input.handle-only::-webkit-slider-thumb { -webkit-appearance: none; width: 14px; height: 14px; margin-top: 3px; border-radius: 50%; background: var(--accent); border: 2px solid #fff; box-shadow: 0 0 0 1px var(--line); cursor: grab; }
        input.handle-only::-moz-range-thumb { width: 14px; height: 14px; border-radius: 50%; background: var(--accent); border: 2px solid #fff; box-shadow: 0 0 0 1px var(--line); cursor: grab; }
        .paint-layer { display: grid; gap: 8px; }
        .color-field { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 13px; flex-wrap: wrap; }
        .color-label { min-width: 72px; }
        .color-inputs { display: flex; align-items: center; gap: 6px; flex: 1; min-width: 160px; }
        .color-inputs input[type=color] { width: 44px; flex: none; }
        .color-inputs output { width: 38px; text-align: right; font-variant-numeric: tabular-nums; }
        .php { width: min(100%, 560px); position: relative; border: 1px solid var(--line); border-radius: 10px; background: var(--surface); padding: 10px 12px; }
        .php summary { margin: 0; }
        .php .copy { position: absolute; top: 8px; right: 10px; }
        .php pre { margin: 8px 0 0; overflow: auto; font-size: 12.5px; line-height: 1.5; }
    </style>
</head>
<body>
    <header><strong>laravel-qr</strong><span>playground</span></header>
    <main>{{ $slot }}</main>
</body>
</html>
