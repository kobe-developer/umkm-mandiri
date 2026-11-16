const rupiahFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

/** Format Rupiah tanpa desimal: Rp13.000 */
export function rupiah(value) {
    const amount = Math.round(Number(value) || 0);
    const sign = amount < 0 ? '-' : '';
    return `${sign}Rp${rupiahFormatter.format(Math.abs(amount))}`;
}

const TZ = 'Asia/Jakarta';

export function dateTime(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: TZ }).format(new Date(value));
}

export function date(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: TZ }).format(new Date(value));
}

export function time(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', { timeStyle: 'short', timeZone: TZ }).format(new Date(value));
}

export function shortDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', timeZone: TZ }).format(new Date(value));
}

/** Persentase perubahan, null = tidak ada pembanding */
export function percent(value) {
    if (value === null || value === undefined) return '–';
    const sign = value > 0 ? '+' : '';
    return `${sign}${value}%`;
}

/** Normalisasi nomor WA (sama dengan App\Support\PhoneNumber di server) */
export function normalizePhone(input) {
    const digits = String(input ?? '').trim().replace(/[\s\-.()]/g, '');
    if (!/^\+?\d+$/.test(digits)) return null;
    let number = digits.replace(/^\+/, '');
    if (number.startsWith('62')) number = number.slice(2);
    else if (number.startsWith('0')) number = number.slice(1);
    if (!number.startsWith('8') || number.length < 8 || number.length > 13) return null;
    return `62${number}`;
}

/** Ambil nilai numerik dari input uang ("13.000" → 13000) */
export function parseMoney(value) {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits === '' ? null : Number(digits);
}
