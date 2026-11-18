/**
 * Harga promo untuk tampilan POS (harga final tetap dihitung ulang di server).
 * product.promo = { price, channels, description, active_until } | null
 */
export function promoPrice(product, channel) {
    const promo = product?.promo;
    if (!promo || !promo.channels?.includes(channel) || promo.price >= product.price) return null;
    return promo.price;
}

export function effectivePrice(product, channel) {
    return promoPrice(product, channel) ?? product?.price ?? 0;
}
