{{-- The PHP that makes the code on screen, with a copy button. --}}
@props(['php'])
<details class="php" open wire:ignore.self x-data="{ copied: false }">
    <summary>PHP</summary>
    <button
        type="button"
        class="copy"
        @click="navigator.clipboard.writeText($refs.php.textContent).then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
        x-text="copied ? 'Copied' : 'Copy'"
    >Copy</button>
    <pre x-ref="php"><code>{{ $php }}</code></pre>
</details>
