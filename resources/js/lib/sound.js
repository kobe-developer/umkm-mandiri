let audioContext;

/** Bunyi notifikasi pendek tanpa file audio (Web Audio API). */
export function playNotification() {
    try {
        audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
        const notes = [880, 1175, 1568];
        notes.forEach((frequency, index) => {
            const start = audioContext.currentTime + index * 0.16;
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0.0001, start);
            gain.gain.exponentialRampToValueAtTime(0.3, start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.15);
            oscillator.connect(gain).connect(audioContext.destination);
            oscillator.start(start);
            oscillator.stop(start + 0.16);
        });
    } catch (error) {
        // Browser memblokir audio sebelum ada interaksi; badge tetap tampil.
        console.warn('Notifikasi suara diblokir browser.', error);
    }
}

/** Panggil saat interaksi pertama agar audio diizinkan browser. */
export function unlockAudio() {
    try {
        audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
        if (audioContext.state === 'suspended') audioContext.resume();
    } catch {
        // abaikan: tidak semua browser mendukung
    }
}
