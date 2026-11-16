/**
 * Keranjang menu online, disimpan di perangkat pelanggan (localStorage)
 * agar tidak hilang saat halaman di-refresh.
 * Harga TIDAK disimpan di sini: total selalu dihitung dari data produk terbaru
 * (dan dihitung ulang di server saat checkout).
 */
const STORAGE_KEY = 'umkm-cart-v1';
const MAX_QUANTITY = 50;
const MAX_NOTES_LENGTH = 100;

function sanitize(raw) {
    if (!Array.isArray(raw)) return [];
    return raw
        .filter((item) => item && Number.isInteger(item.product_id) && Number.isInteger(item.quantity) && item.quantity > 0)
        .map((item) => ({
            product_id: item.product_id,
            quantity: Math.min(item.quantity, MAX_QUANTITY),
            notes: typeof item.notes === 'string' ? item.notes.slice(0, MAX_NOTES_LENGTH) : '',
        }));
}

function load() {
    try {
        if (typeof localStorage === 'undefined') return [];
        return sanitize(JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]'));
    } catch {
        return [];
    }
}

let items = $state(load());

function commit(next) {
    items = next;
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    } catch {
        // Penyimpanan diblokir (mode privat / penuh): keranjang tetap jalan di memori.
    }
}

function toMap(products) {
    return products instanceof Map ? products : new Map(products.map((p) => [p.id, p]));
}

export const cart = {
    get items() {
        return items;
    },

    quantityOf(productId) {
        return items.find((item) => item.product_id === productId)?.quantity ?? 0;
    },

    add(productId) {
        if (items.some((item) => item.product_id === productId)) {
            this.increment(productId);
            return;
        }
        commit([...items, { product_id: productId, quantity: 1, notes: '' }]);
    },

    increment(productId) {
        commit(items.map((item) => (item.product_id === productId ? { ...item, quantity: Math.min(item.quantity + 1, MAX_QUANTITY) } : item)));
    },

    decrement(productId) {
        commit(
            items
                .map((item) => (item.product_id === productId ? { ...item, quantity: item.quantity - 1 } : item))
                .filter((item) => item.quantity > 0),
        );
    },

    setQuantity(productId, quantity) {
        if (quantity <= 0) {
            this.remove(productId);
            return;
        }
        commit(items.map((item) => (item.product_id === productId ? { ...item, quantity: Math.min(quantity, MAX_QUANTITY) } : item)));
    },

    remove(productId) {
        commit(items.filter((item) => item.product_id !== productId));
    },

    setNotes(productId, notes) {
        commit(items.map((item) => (item.product_id === productId ? { ...item, notes: String(notes ?? '').slice(0, MAX_NOTES_LENGTH) } : item)));
    },

    clear() {
        commit([]);
    },

    count() {
        return items.reduce((sum, item) => sum + item.quantity, 0);
    },

    subtotal(products) {
        const map = toMap(products);
        return items.reduce((sum, item) => sum + (map.get(item.product_id)?.price ?? 0) * item.quantity, 0);
    },

    /** Buang item yang produknya sudah tidak ada di menu online. */
    prune(products) {
        const map = toMap(products);
        const next = items.filter((item) => map.has(item.product_id));
        if (next.length !== items.length) commit(next);
    },

    /** Payload untuk POST /menu/checkout */
    payload() {
        return items.map((item) => ({ product_id: item.product_id, quantity: item.quantity, notes: item.notes?.trim() || null }));
    },
};

export const CART_MAX_QUANTITY = MAX_QUANTITY;
