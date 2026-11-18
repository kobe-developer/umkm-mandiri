/**
 * Utilitas bersama untuk dropdown (SearchSelect, DatePicker).
 */

/** Action: panggil callback saat pointer ditekan di luar elemen. */
export function clickOutside(node, callback) {
    let handler = callback;
    const onPointerDown = (event) => {
        if (!node.contains(event.target)) handler?.(event);
    };
    document.addEventListener('pointerdown', onPointerDown, true);
    return {
        update(next) {
            handler = next;
        },
        destroy() {
            document.removeEventListener('pointerdown', onPointerDown, true);
        },
    };
}

const GAP = 6;
const EDGE = 8;

/**
 * Posisi panel `position: fixed` di bawah (atau di atas, bila ruang bawah kurang) trigger.
 * Fixed agar panel tidak terpotong container ber-overflow (mis. isi Modal).
 * @returns {string} nilai atribut style
 */
export function anchorStyle(trigger, { height, width = null }) {
    if (!trigger) return '';
    const rect = trigger.getBoundingClientRect();
    const panelWidth = Math.min(width ?? rect.width, window.innerWidth - EDGE * 2);
    const left = Math.max(EDGE, Math.min(rect.left, window.innerWidth - panelWidth - EDGE));
    const below = window.innerHeight - rect.bottom - EDGE;
    const above = rect.top - EDGE;
    const vertical = below < height && above > below
        ? `bottom:${window.innerHeight - rect.top + GAP}px;max-height:${above - GAP}px`
        : `top:${rect.bottom + GAP}px;max-height:${below - GAP}px`;
    return `left:${left}px;width:${panelWidth}px;${vertical}`;
}

/** Pasang listener scroll (semua container) & resize; mengembalikan fungsi pelepas untuk $effect. */
export function onViewportChange(callback) {
    window.addEventListener('scroll', callback, true);
    window.addEventListener('resize', callback);
    return () => {
        window.removeEventListener('scroll', callback, true);
        window.removeEventListener('resize', callback);
    };
}
