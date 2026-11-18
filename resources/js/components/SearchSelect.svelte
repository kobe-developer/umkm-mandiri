<script>
    import { tick } from 'svelte';
    import ChevronDown from '@lucide/svelte/icons/chevron-down';
    import Search from '@lucide/svelte/icons/search';
    import Check from '@lucide/svelte/icons/check';
    import { clickOutside, anchorStyle, onViewportChange } from '../lib/popover.js';

    /**
     * Pilihan dengan pencarian (ala select2).
     * options: [{ value, label, hint? }] — hint tampil di kanan (mis. harga).
     */
    let {
        options = [],
        value = $bindable(null),
        id = undefined,
        placeholder = 'Pilih…',
        searchPlaceholder = 'Cari…',
        emptyText = 'Tidak ada hasil',
        invalid = false,
        onchange,
    } = $props();

    /** Batas baris yang dirender sekaligus agar tetap ringan untuk daftar panjang. */
    const MAX_VISIBLE = 100;
    const PANEL_HEIGHT = 320;
    const uid = `ss-${Math.random().toString(36).slice(2, 9)}`;

    let open = $state(false);
    let query = $state('');
    let activeIndex = $state(0);
    let panelStyle = $state('');
    let trigger = $state();
    let searchInput = $state();
    let listbox = $state();

    const selected = $derived(options.find((option) => option.value === value) ?? null);
    const matches = $derived.by(() => {
        const term = query.trim().toLowerCase();
        const found = term ? options.filter((option) => option.label.toLowerCase().includes(term)) : options;
        return found.slice(0, MAX_VISIBLE);
    });

    async function openPanel() {
        if (open) return;
        query = '';
        panelStyle = anchorStyle(trigger, { height: PANEL_HEIGHT });
        open = true;
        const current = options.findIndex((option) => option.value === value);
        activeIndex = Math.max(0, current);
        await tick();
        searchInput?.focus();
        scrollActiveIntoView();
    }

    // Panel fixed mengikuti trigger saat isi modal/halaman di-scroll
    $effect(() => {
        if (!open) return;
        return onViewportChange(() => (panelStyle = anchorStyle(trigger, { height: PANEL_HEIGHT })));
    });

    function close(restoreFocus = false) {
        open = false;
        if (restoreFocus) trigger?.focus();
    }

    function choose(option) {
        value = option.value;
        onchange?.(option.value);
        close(true);
    }

    async function scrollActiveIntoView() {
        await tick();
        listbox?.querySelector(`#${uid}-opt-${activeIndex}`)?.scrollIntoView({ block: 'nearest' });
    }

    function move(delta) {
        if (matches.length === 0) return;
        activeIndex = (activeIndex + delta + matches.length) % matches.length;
        scrollActiveIntoView();
    }

    function onSearchKeydown(event) {
        const actions = {
            ArrowDown: () => move(1),
            ArrowUp: () => move(-1),
            Home: () => { activeIndex = 0; scrollActiveIntoView(); },
            End: () => { activeIndex = Math.max(0, matches.length - 1); scrollActiveIntoView(); },
            Enter: () => matches[activeIndex] && choose(matches[activeIndex]),
            Escape: () => close(true),
        };
        const action = actions[event.key];
        if (!action) return;
        event.preventDefault();
        event.stopPropagation(); // Escape tidak ikut menutup modal
        action();
    }

    function onTriggerKeydown(event) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
            event.preventDefault();
            openPanel();
        }
    }
</script>

<div class="relative" use:clickOutside={() => close()} onfocusout={(e) => open && !e.currentTarget.contains(e.relatedTarget) && close()}>
    <button
        bind:this={trigger}
        {id}
        type="button"
        class="input flex items-center gap-2 text-left {invalid ? 'border-brand-500' : ''} {open ? 'ring-2 ring-ink/10' : ''}"
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-controls="{uid}-list"
        onclick={() => (open ? close() : openPanel())}
        onkeydown={onTriggerKeydown}
    >
        <span class="min-w-0 flex-1 truncate {selected ? 'text-ink' : 'text-faint'}">{selected?.label ?? placeholder}</span>
        {#if selected?.hint}<span class="num shrink-0 text-xs text-muted">{selected.hint}</span>{/if}
        <ChevronDown size={16} class="shrink-0 text-muted transition-transform {open ? 'rotate-180' : ''}" aria-hidden="true" />
    </button>

    {#if open}
        <div class="fixed z-[70] flex animate-fade-in flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-float" style={panelStyle}>
            <div class="relative border-b border-line p-2">
                <Search size={16} class="pointer-events-none absolute top-1/2 left-5 -translate-y-1/2 text-faint" aria-hidden="true" />
                <input
                    bind:this={searchInput}
                    bind:value={query}
                    type="text"
                    class="input min-h-10 rounded-xl pl-9 text-sm"
                    role="combobox"
                    aria-label={searchPlaceholder}
                    aria-autocomplete="list"
                    aria-expanded="true"
                    aria-controls="{uid}-list"
                    aria-activedescendant={matches[activeIndex] ? `${uid}-opt-${activeIndex}` : undefined}
                    placeholder={searchPlaceholder}
                    autocomplete="off"
                    onkeydown={onSearchKeydown}
                    oninput={() => (activeIndex = 0)}
                />
            </div>
            <ul bind:this={listbox} id="{uid}-list" role="listbox" class="max-h-64 min-h-0 flex-1 overflow-y-auto overscroll-contain p-1.5">
                {#each matches as option, index (option.value)}
                    {@const isSelected = option.value === value}
                    <!-- Keyboard ditangani input pencarian (pola combobox + aria-activedescendant) -->
                    <!-- svelte-ignore a11y_click_events_have_key_events -->
                    <li
                        id="{uid}-opt-{index}"
                        role="option"
                        aria-selected={isSelected}
                        class="flex min-h-10 cursor-pointer items-center gap-2 rounded-xl px-3 text-sm transition-colors {index === activeIndex ? 'bg-paper-deep' : ''} {isSelected ? 'font-semibold text-ink' : 'text-ink-soft'}"
                        onpointerenter={() => (activeIndex = index)}
                        onpointerdown={(e) => e.preventDefault()}
                        onclick={() => choose(option)}
                    >
                        <span class="min-w-0 flex-1 truncate">{option.label}</span>
                        {#if option.hint}<span class="num shrink-0 text-xs text-muted">{option.hint}</span>{/if}
                        <Check size={15} class="shrink-0 text-brand-600 {isSelected ? '' : 'invisible'}" aria-hidden="true" />
                    </li>
                {:else}
                    <li class="px-3 py-6 text-center text-sm text-muted" role="presentation">{emptyText}</li>
                {/each}
            </ul>
        </div>
    {/if}
</div>
