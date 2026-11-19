/**
 * Membaca durasi file audio di browser (detik, dibulatkan).
 * Memakai elemen Audio sementara yang tidak diputar; mengembalikan null jika gagal.
 */
export function readAudioDuration(file, timeoutMs = 8000) {
    return new Promise((resolve) => {
        const url = URL.createObjectURL(file);
        const audio = new Audio();
        let done = false;
        let timer = null;

        const finish = (value) => {
            if (done) return;
            done = true;
            clearTimeout(timer);
            audio.removeAttribute('src');
            audio.load();
            URL.revokeObjectURL(url);
            resolve(value);
        };

        timer = setTimeout(() => finish(null), timeoutMs);

        audio.preload = 'metadata';
        audio.onloadedmetadata = () => {
            finish(Number.isFinite(audio.duration) ? Math.round(audio.duration) : null);
        };
        audio.onerror = () => finish(null);
        audio.src = url;
    });
}