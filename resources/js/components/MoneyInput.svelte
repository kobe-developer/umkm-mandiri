<script>
    import { parseMoney } from '../lib/format.js';

    /** Input uang dengan pemisah ribuan; value berupa angka (atau null). */
    let { value = $bindable(null), id = undefined, placeholder = '0', class: className = '', autofocus = false, ...rest } = $props();

    const display = $derived(value === null || value === undefined ? '' : new Intl.NumberFormat('id-ID').format(value));

    function onInput(event) {
        value = parseMoney(event.currentTarget.value);
    }
</script>

<div class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-stone-500">Rp</span>
    <!-- svelte-ignore a11y_autofocus -->
    <input {id} type="text" inputmode="numeric" class="input pl-9 tabular-nums {className}" {placeholder} value={display} oninput={onInput} {autofocus} {...rest} />
</div>
