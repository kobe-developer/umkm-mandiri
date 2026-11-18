<script>
    import { tick } from 'svelte';
    import CalendarDays from '@lucide/svelte/icons/calendar-days';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import { clickOutside, anchorStyle, onViewportChange } from '../lib/popover.js';

    /**
     * Pemilih tanggal (kalender) berbahasa Indonesia, minggu dimulai Senin.
     * value/min/today berupa string 'YYYY-MM-DD'. Hanya tanggal kalender (tanpa jam),
     * jadi zona waktu browser tidak berpengaruh; `today` sebaiknya dari server (WIB).
     */
    let { value = $bindable(''), min = null, today = null, id = undefined, placeholder = 'Pilih tanggal', invalid = false, onchange } = $props();

    const PANEL_HEIGHT = 380;
    const PANEL_WIDTH = 296;
    const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    const monthFormatter = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric', timeZone: 'UTC' });
    const displayFormatter = new Intl.DateTimeFormat('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
    const fullFormatter = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });

    // --- Utilitas tanggal berbasis UTC (aman dari pergeseran zona waktu) ---
    const pad = (n) => String(n).padStart(2, '0');
    const toIso = (date) => `${date.getUTCFullYear()}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())}`;
    function parseIso(iso) {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso ?? '');
        return match ? new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]))) : null;
    }
    const addDays = (iso, days) => {
        const date = parseIso(iso);
        date.setUTCDate(date.getUTCDate() + days);
        return toIso(date);
    };
    const localToday = () => {
        const now = new Date();
        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
    };

    const todayIso = $derived(today ?? localToday());

    let open = $state(false);
    let panelStyle = $state('');
    let focusedIso = $state('');
    let trigger = $state();
    let grid = $state();

    // Bulan yang ditampilkan mengikuti tanggal yang sedang difokus
    const viewDate = $derived(parseIso(focusedIso) ?? parseIso(todayIso));
    const monthLabel = $derived(viewDate ? monthFormatter.format(viewDate) : '');
    const days = $derived.by(() => {
        if (!viewDate) return [];
        const first = new Date(Date.UTC(viewDate.getUTCFullYear(), viewDate.getUTCMonth(), 1));
        const offset = (first.getUTCDay() + 6) % 7; // Senin = 0
        const start = toIso(first);
        return Array.from({ length: 42 }, (_, index) => {
            const iso = addDays(start, index - offset);
            return { iso, day: Number(iso.slice(8)), inMonth: iso.slice(0, 7) === start.slice(0, 7) };
        });
    });

    const isDisabled = (iso) => Boolean(min) && iso < min;
    const display = $derived(parseIso(value) ? displayFormatter.format(parseIso(value)) : '');

    async function focusDay(iso) {
        focusedIso = iso;
        await tick();
        grid?.querySelector(`[data-iso="${iso}"]`)?.focus();
    }

    async function openPanel() {
        panelStyle = anchorStyle(trigger, { height: PANEL_HEIGHT, width: PANEL_WIDTH });
        open = true;
        const start = value || (min && min > todayIso ? min : todayIso);
        await focusDay(start);
    }

    $effect(() => {
        if (!open) return;
        return onViewportChange(() => (panelStyle = anchorStyle(trigger, { height: PANEL_HEIGHT, width: PANEL_WIDTH })));
    });

    function close(restoreFocus = false) {
        open = false;
        if (restoreFocus) trigger?.focus();
    }

    function choose(iso) {
        if (isDisabled(iso)) return;
        value = iso;
        onchange?.(iso);
        close(true);
    }

    function shiftMonth(delta) {
        const date = parseIso(focusedIso);
        const target = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + delta, 1));
        const lastDay = new Date(Date.UTC(target.getUTCFullYear(), target.getUTCMonth() + 1, 0)).getUTCDate();
        target.setUTCDate(Math.min(date.getUTCDate(), lastDay));
        return toIso(target);
    }

    function onGridKeydown(event) {
        const moves = {
            ArrowLeft: () => addDays(focusedIso, -1),
            ArrowRight: () => addDays(focusedIso, 1),
            ArrowUp: () => addDays(focusedIso, -7),
            ArrowDown: () => addDays(focusedIso, 7),
            PageUp: () => shiftMonth(-1),
            PageDown: () => shiftMonth(1),
        };
        if (moves[event.key]) {
            event.preventDefault();
            focusDay(moves[event.key]());
        } else if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation(); // tidak ikut menutup modal
            close(true);
        }
    }
</script>

<div class="relative" use:clickOutside={() => close()} onfocusout={(e) => open && !e.currentTarget.contains(e.relatedTarget) && close()}>
    <button
        bind:this={trigger}
        {id}
        type="button"
        class="input flex items-center gap-2 text-left {invalid ? 'border-brand-500' : ''} {open ? 'ring-2 ring-ink/10' : ''}"
        aria-haspopup="dialog"
        aria-expanded={open}
        onclick={() => (open ? close() : openPanel())}
    >
        <CalendarDays size={17} class="shrink-0 text-muted" aria-hidden="true" />
        <span class="num min-w-0 flex-1 truncate {display ? 'text-ink' : 'text-faint'}">{display || placeholder}</span>
    </button>

    {#if open}
        <div
            class="fixed z-[70] animate-fade-in overflow-y-auto rounded-2xl border border-line bg-surface p-3 shadow-float"
            style={panelStyle}
            role="dialog"
            aria-label="Pilih tanggal"
        >
            <div class="mb-2 flex items-center justify-between">
                <button type="button" class="btn-icon size-9" aria-label="Bulan sebelumnya" onclick={() => focusDay(shiftMonth(-1))}><ChevronLeft size={18} /></button>
                <p class="text-sm font-semibold text-ink capitalize" aria-live="polite">{monthLabel}</p>
                <button type="button" class="btn-icon size-9" aria-label="Bulan berikutnya" onclick={() => focusDay(shiftMonth(1))}><ChevronRight size={18} /></button>
            </div>

            <div class="grid grid-cols-7 text-center text-[11px] font-semibold tracking-wide text-faint uppercase" aria-hidden="true">
                {#each WEEKDAYS as weekday (weekday)}<span class="py-1">{weekday}</span>{/each}
            </div>

            <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
            <div bind:this={grid} class="grid grid-cols-7 gap-0.5" role="group" aria-label={monthLabel} onkeydown={onGridKeydown}>
                {#each days as cell (cell.iso)}
                    {@const selected = cell.iso === value}
                    {@const disabled = isDisabled(cell.iso)}
                    {@const isToday = cell.iso === todayIso}
                    <button
                        type="button"
                        data-iso={cell.iso}
                        tabindex={cell.iso === focusedIso ? 0 : -1}
                        {disabled}
                        aria-pressed={selected}
                        aria-label={fullFormatter.format(parseIso(cell.iso))}
                        class="num flex aspect-square items-center justify-center rounded-full text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-brand-600 disabled:cursor-not-allowed disabled:text-line-strong
                            {selected ? 'bg-ink font-semibold text-paper' : isToday ? 'font-semibold text-brand-700 ring-1 ring-brand-200' : cell.inMonth ? 'text-ink hover:bg-paper-deep' : 'text-faint hover:bg-paper-deep'}"
                        onclick={() => choose(cell.iso)}
                    >{cell.day}</button>
                {/each}
            </div>

            <div class="mt-2 flex justify-between border-t border-line pt-2">
                <button type="button" class="btn-ghost btn-sm" disabled={isDisabled(todayIso)} onclick={() => choose(todayIso)}>Hari ini</button>
                <button type="button" class="btn-ghost btn-sm text-muted" onclick={() => close(true)}>Tutup</button>
            </div>
        </div>
    {/if}
</div>
