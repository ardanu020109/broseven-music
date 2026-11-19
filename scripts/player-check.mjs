// Pemeriksaan logika store player tanpa browser.
// Jalankan dari folder proyek: node scripts/player-check.mjs

class FakeAudio {
    constructor() {
        this.listeners = {};
        this.attrs = {};
        this.paused = true;
        this.currentTime = 0;
        this.duration = NaN;
        this.volume = 1;
        this.muted = false;
        this.seeking = false;
        this.preload = '';
        globalThis.__fakeAudio = this;
    }
    set src(value) { this.attrs.src = value; }
    get src() { return this.attrs.src ?? ''; }
    addEventListener(type, fn) { (this.listeners[type] ||= []).push(fn); }
    emit(type) { for (const fn of this.listeners[type] ?? []) fn(); }
    getAttribute(name) { return this.attrs[name] ?? null; }
    removeAttribute(name) { delete this.attrs[name]; }
    load() { this.currentTime = 0; }
    play() {
        if (this.paused) { this.paused = false; this.emit('play'); }
        return Promise.resolve();
    }
    pause() {
        if (!this.paused) { this.paused = true; this.emit('pause'); }
    }
}

globalThis.Audio = FakeAudio;

const { createPinia, setActivePinia } = await import('pinia');
const { usePlayerStore } = await import('../resources/js/Stores/player.js');

setActivePinia(createPinia());

const p = usePlayerStore();
const audio = globalThis.__fakeAudio;

let passed = 0;
let failed = 0;

function check(label, ok) {
    if (ok) {
        passed += 1;
        console.log(`  [OK] ${label}`);
    } else {
        failed += 1;
        console.log(`  [X]  ${label}`);
    }
}

const songs = Array.from({ length: 5 }, (_, i) => ({
    id: i + 1,
    title: `Lagu ${i + 1}`,
    artist: { name: 'Artis' },
    album: { name: 'Album' },
    cover_url: null,
    stream_url: `/music/${i + 1}/stream`,
    download_url: `/music/${i + 1}/download`,
    duration: 100,
}));

const ids = () => p.playOrder.map((item) => item.song.id);
const sameSet = (a, b) => JSON.stringify([...a].sort((x, y) => x - y)) === JSON.stringify([...b].sort((x, y) => x - y));
const endSong = () => { audio.pause(); audio.emit('ended'); };

console.log('Dasar');
p.playQueue(songs, 0);
check('playQueue memutar lagu pertama', p.current?.id === 1 && p.playing === true && audio.src === '/music/1/stream');
p.toggle();
check('toggle -> jeda', p.playing === false && audio.paused === true);
p.toggle();
check('toggle -> lanjut', p.playing === true);
audio.currentTime = 30;
p.stop();
check('stop -> posisi 0 dan berhenti', audio.currentTime === 0 && p.playing === false && p.current?.id === 1);
p.next();
check('next -> lagu 2', p.current?.id === 2 && audio.src === '/music/2/stream');
audio.currentTime = 10;
p.prev();
check('prev setelah >3 detik -> ulang lagu yang sama', p.current?.id === 2 && audio.currentTime === 0);
p.prev();
check('prev di awal lagu -> lagu sebelumnya (1)', p.current?.id === 1);
p.prev();
check('prev di lagu pertama (repeat off) -> tetap lagu 1', p.current?.id === 1);
p.seek(42);
check('seek ke 42 detik', audio.currentTime === 42);
p.seek(9999);
check('seek dibatasi durasi (100)', audio.currentTime === 100);
p.seek(-5);
check('seek tidak negatif', audio.currentTime === 0);
p.close();

console.log('Repeat');
check('Siklus repeat: off -> all -> one -> off', (() => {
    const seq = [p.repeat];
    p.cycleRepeat(); seq.push(p.repeat);
    p.cycleRepeat(); seq.push(p.repeat);
    p.cycleRepeat(); seq.push(p.repeat);
    return seq.join(',') === 'off,all,one,off';
})());

p.playQueue(songs, 4);
check('repeat off: lagu terakhir -> hasNext false', p.hasNext === false);
endSong();
check('repeat off: setelah lagu terakhir selesai, player berhenti', p.playing === false && p.current?.id === 5);
p.close();

p.setRepeat('all');
p.playQueue(songs, 4);
check('repeat all: lagu terakhir -> hasNext true', p.hasNext === true);
endSong();
check('repeat all: kembali ke lagu 1 dan terus diputar', p.current?.id === 1 && p.playing === true);
p.close();

p.setRepeat('one');
p.playQueue(songs, 2);
audio.currentTime = 50;
endSong();
check('repeat one: lagu yang sama diulang dari 0', p.current?.id === 3 && audio.currentTime === 0 && p.playing === true);
p.next();
check('repeat one: tombol next tetap pindah lagu', p.current?.id === 4);
p.setRepeat('off');
p.close();

console.log('Shuffle');
p.toggleShuffle();
check('shuffle aktif', p.shuffle === true);
p.playQueue(songs, 2);
check('shuffle: lagu awal ada di urutan pertama', ids()[0] === 3 && p.current?.id === 3);
check('shuffle: urutan memuat semua lagu tepat sekali', sameSet(ids(), [1, 2, 3, 4, 5]) && new Set(ids()).size === 5);

const seen = [p.current.id];
for (let i = 0; i < 4; i++) { p.next(); seen.push(p.current.id); }
check('shuffle: satu putaran memutar semua lagu tanpa pengulangan', new Set(seen).size === 5);
check('shuffle: di lagu terakhir (repeat off) hasNext false', p.hasNext === false);
p.close();

p.setRepeat('all');
let wrapOk = true;
let allOk = true;
p.playQueue(songs, 0);
for (let round = 0; round < 200; round++) {
    while (p.pos < p.order.length - 1) p.next();
    const lastId = p.current.id;
    endSong();
    if (p.current.id === lastId) wrapOk = false;
    if (!sameSet(ids(), [1, 2, 3, 4, 5]) || p.pos !== 0) allOk = false;
}
check('shuffle + repeat all: lagu terakhir tidak langsung diulang (200 putaran)', wrapOk);
check('shuffle + repeat all: urutan baru selalu lengkap dan mulai dari posisi 0', allOk);
p.close();

p.playQueue([songs[0]], 0);
endSong();
check('shuffle + repeat all dengan 1 lagu: diputar ulang tanpa error', p.current?.id === 1 && p.playing === true);
p.close();
p.setRepeat('off');

p.playQueue(songs, 3);
p.toggleShuffle();
check('matikan shuffle: urutan kembali normal dan lagu aktif tetap', p.shuffle === false
    && JSON.stringify(ids()) === JSON.stringify([1, 2, 3, 4, 5]) && p.current?.id === 4 && p.pos === 3);
p.close();
p.playQueue(songs, 1);
p.next();
p.toggleShuffle();
check('nyalakan shuffle di tengah antrean: lagu aktif tetap dan jadi urutan pertama', p.current?.id === 3 && ids()[0] === 3);
p.toggleShuffle();
p.close();

console.log('Antrean');
p.playQueue(songs, 0);
p.playFrom(songs, 0);
check('playFrom pada lagu aktif -> toggle (jeda)', p.playing === false && p.current?.id === 1);
p.playFrom(songs, 3);
check('playFrom pada lagu lain -> memutar lagu itu', p.current?.id === 4 && p.playing === true);
p.playPosition(1);
check('playPosition(1) -> lagu 2', p.current?.id === 2);
p.close();

p.playQueue(songs, 2);
p.removeSong(1);
check('removeSong(lagu lain): antrean 4 lagu, lagu aktif tetap 3', p.queue.length === 4 && p.current?.id === 3
    && JSON.stringify(ids()) === JSON.stringify([2, 3, 4, 5]));
p.removeSong(3);
check('removeSong(lagu aktif): player ditutup', p.hasTrack === false && p.queue.length === 0);

p.toggleShuffle();
p.playQueue(songs, 0);
p.removeSong(5);
check('removeSong dengan shuffle: urutan konsisten', p.queue.length === 4 && p.current?.id === 1
    && sameSet(ids(), [1, 2, 3, 4]) && p.order.every((i) => i >= 0 && i < 4));
p.toggleShuffle();
p.close();

console.log('Volume dan penutupan');
p.setVolume(0.3);
check('setVolume(0.3) -> volume audio 0.3', audio.volume === 0.3 && audio.muted === false);
p.toggleMute();
check('toggleMute -> audio bisu', audio.muted === true);
p.toggleMute();
check('toggleMute lagi -> suara aktif', audio.muted === false);
p.setVolume(0);
p.toggleMute();
check('volume 0 lalu toggleMute -> volume pulih (0.6)', audio.volume === 0.6 && audio.muted === false);
p.playQueue(songs, 0);
p.close();
check('close: kosongkan antrean dan lepas sumber audio', p.hasTrack === false && p.queue.length === 0
    && audio.getAttribute('src') === null && p.playing === false);

console.log('');
console.log(failed === 0 ? `SEMUA PENGECEKAN LULUS (${passed})` : `ADA ${failed} PENGECEKAN YANG GAGAL (lulus: ${passed})`);
process.exit(failed === 0 ? 0 : 1);