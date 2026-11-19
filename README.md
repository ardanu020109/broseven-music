# BroSeven! Music

Aplikasi web **pemutar musik dan pengelola koleksi musik pribadi**. Backend memakai Laravel 13, frontend memakai Vue 3 melalui Inertia.js, dan keduanya berada dalam **satu proyek** (bukan dua proyek terpisah).

- Dua peran: **Superadmin** dan **Listener**
- Koleksi pribadi per listener: lagu, album, artis, playlist, dan favorit
- Pemutar dengan visual piringan hitam (vinyl) yang **tidak berhenti saat berpindah halaman**
- File audio disimpan **privat** dan dilayani lewat streaming berotorisasi (mendukung HTTP Range)

> **Status:** dikembangkan bertahap. Fitur **lirik lagu belum diimplementasikan** dan **tes otomatis PHPUnit belum ditulis** (lihat [Keterbatasan dan Rencana](#keterbatasan-dan-rencana) dan [Pengujian](#pengujian)).

## Daftar Isi

- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Database](#database)
- [Seeder dan akun pertama](#seeder-dan-akun-pertama)
- [Penyimpanan file](#penyimpanan-file)
- [Cara menjalankan](#cara-menjalankan)
- [Hak akses](#hak-akses)
- [Pemutar musik](#pemutar-musik)
- [Endpoint utama](#endpoint-utama)
- [Keamanan](#keamanan)
- [Pengujian](#pengujian)
- [Struktur proyek](#struktur-proyek)
- [Keterbatasan dan Rencana](#keterbatasan-dan-rencana)
- [Pemecahan masalah](#pemecahan-masalah)
- [Lisensi](#lisensi)

## Fitur

### Superadmin

- **Dashboard:** total listener (aktif dan nonaktif), total musik, album, artis, playlist, total penyimpanan, listener terbaru, dan upload musik terbaru.
- **Manage Listeners:** daftar dengan pencarian (nama atau email), filter status, dan pagination; tambah, ubah, lihat detail, aktifkan/nonaktifkan, dan hapus listener.
  - Menonaktifkan listener atau mengganti passwordnya langsung memutus semua sesi loginnya.
  - Menghapus listener menghapus **seluruh datanya beserta file fisiknya** (audio, foto artis, cover album, cover playlist).
- **Uploaded Music:** semua lagu dari semua listener (judul, album, artis, pemilik, cover, tanggal upload, durasi) dengan pencarian (judul, artis, album, pemilik), filter pemilik, putar, unduh, dan halaman detail. **Tanpa edit dan hapus.**
- **Profile:** ubah nama dan password. Email superadmin dikunci karena bersumber dari `ADMIN_EMAIL` di `.env`.

### Listener

- **Beranda (`/home`):** sapaan, pencarian lintas kategori, ringkasan koleksi, Recently Added, Recently Played, Album Collection, Artist Collection, Playlist Collection, Favorite Songs, dan All Songs, lengkap dengan tampilan koleksi kosong.
- **My Songs:** unggah, ubah, ganti file, hapus, putar, unduh, pencarian, filter album dan artis, serta pagination.
- **Albums dan Artists:** CRUD, upload cover/foto, pencarian, filter artis (album), halaman detail (album berurutan menurut nomor track).
- **Playlists:** CRUD, cover, tambah lagu (tanpa duplikat), keluarkan lagu, ubah urutan (seret atau tombol naik/turun), dan putar playlist.
- **Favorites:** ikon hati pink untuk menambah/menghapus favorit, daftar favorit, putar, dan unduh.
- **Profile:** ubah nama, email, dan password.

### Pemutar musik

Vinyl dengan cover album di tengah, kontrol lengkap (previous, play/pause, next, stop, shuffle, repeat, seek, volume, mute), antrean, auto-next, dan musik yang terus berjalan saat berpindah halaman. Perilaku rinci ada di bagian [Pemutar musik](#pemutar-musik).

## Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3+, Eloquent ORM, Laravel Storage, session authentication, Policy, Form Request, Service layer |
| Frontend | Vue 3 (Composition API), Inertia.js 2, Pinia, Axios, Vite |
| Tampilan | Bootstrap 5 dan Bootstrap Icons (keduanya lewat NPM, **tanpa CDN**), CSS manual, tanpa Tailwind |
| Database | MySQL 8 |
| Audio | HTML5 Audio API dan Media Session API |

## Persyaratan

- PHP **8.3 atau lebih baru** (Laravel 13 mendukung PHP 8.3 sampai 8.5)
- Ekstensi PHP: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `mbstring`, `openssl`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`
- Composer 2
- Node.js 20.19+ atau 22 LTS (disarankan) dan NPM
- MySQL 8

Periksa versi yang terpasang:

```bash
php -v
composer -V
node -v
npm -v
mysql --version
```

## Instalasi

1. Clone repositori lalu masuk ke foldernya:

   ```bash
   git clone <URL-REPOSITORI-ANDA> broseven-music
   cd broseven-music
   ```

2. Pasang dependensi PHP dan JavaScript:

   ```bash
   composer install
   npm install
   ```

3. Buat file `.env` dan kunci aplikasi:

   ```powershell
   # Windows PowerShell
   Copy-Item .env.example .env
   ```

   ```bash
   # Linux / macOS
   cp .env.example .env
   ```

   ```bash
   php artisan key:generate
   ```

4. Buat database MySQL:

   ```sql
   CREATE DATABASE broseven_music CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. Ubah `.env` sesuai bagian [Konfigurasi](#konfigurasi) (database dan akun superadmin).

6. Naikkan batas upload PHP (lihat bagian [Konfigurasi](#konfigurasi)), lalu jalankan migration dan seeder:

   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. Jalankan aplikasi (lihat [Cara menjalankan](#cara-menjalankan)).

## Konfigurasi

### Variabel `.env`

Ubah atau tambahkan nilai berikut:

```env
APP_NAME="BroSeven! Music"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=broseven_music
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database

# Superadmin (dibaca oleh SuperAdminSeeder)
ADMIN_NAME="Nama Superadmin"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD="ganti-dengan-password-kuat"
```

Variabel opsional:

| Variabel | Default | Keterangan |
|---|---|---|
| `BS7_MEDIA_DISK` | `local` | Disk penyimpanan media. **Harus disk privat.** Jangan diarahkan ke disk `public`. |
| `BS7_AUDIO_MAX_KB` | `51200` | Ukuran maksimal file musik dalam KB (51200 = 50 MB). |

Catatan:
- Hindari karakter `"`, `\`, dan `$` pada nilai password di `.env`.
- Setelah mengubah `.env`, jalankan `php artisan config:clear`.
- Password superadmin **tidak** ditulis di kode. Ia hanya dibaca dari `.env` saat akun dibuat pertama kali.

### Batas upload PHP

PHP secara bawaan hanya mengizinkan upload 2 MB. Untuk lagu, naikkan di `php.ini` (cek lokasinya dengan `php --ini`):

```ini
upload_max_filesize = 64M
post_max_size = 72M
max_execution_time = 120
memory_limit = 256M
```

Nilai `upload_max_filesize` dan `post_max_size` harus lebih besar dari `BS7_AUDIO_MAX_KB`. Restart server PHP setelah mengubah `php.ini`.

## Database

Tabel milik aplikasi:

| Tabel | Isi penting |
|---|---|
| `users` | `role` (`superadmin` atau `listener`), `is_active`, `last_login_at` |
| `artists` | `user_id`, `name` (unik per listener), `biography`, `photo` |
| `albums` | `user_id`, `artist_id` (boleh kosong), `name`, `cover_image`, `release_year`, `description` |
| `songs` | `user_id`, `album_id` dan `artist_id` (boleh kosong), `title`, `audio_path`, `duration`, `file_size`, `mime_type`, `track_number`, `last_played_at`, `lyrics` (disiapkan, belum dipakai) |
| `playlists` | `user_id`, `name`, `description`, `cover_image` |
| `playlist_song` | `playlist_id`, `song_id`, `position`; unik pada (`playlist_id`, `song_id`) |
| `favorites` | `user_id`, `song_id`; unik pada (`user_id`, `song_id`) |

Tabel bawaan Laravel yang ikut dibuat: `password_reset_tokens`, `sessions`, `cache`, `jobs`, dan turunannya.

Perilaku penghapusan:
- Menghapus **user** menghapus seluruh data miliknya (cascade database); file fisik dibersihkan oleh service.
- Menghapus **artis** atau **album** tidak menghapus lagunya, hanya mengosongkan `artist_id` atau `album_id`.
- Menghapus **lagu** menghapus barisnya di playlist dan favorit, serta file audionya.
- Menghapus **playlist** tidak menghapus lagu di dalamnya.

## Seeder dan akun pertama

`php artisan db:seed` menjalankan `SuperAdminSeeder`:

- Membuat superadmin dari `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD`.
- Aman dijalankan berulang: nama dan password akun yang sudah ada **tidak ditimpa**, dan tidak dibuat superadmin kedua.

Langkah pertama setelah instalasi:

1. Buka `/login` lalu masuk dengan akun superadmin dari `.env`.
2. Tambahkan listener lewat **Manage Listeners**. Registrasi publik tidak tersedia.
3. Atau buat listener lewat terminal (password diminta lewat prompt):

   ```bash
   php artisan bs7:create-listener "Nama Listener" listener@example.com
   ```

## Penyimpanan file

Semua file media disimpan di disk **privat** (`storage/app/private`), tanpa `php artisan storage:link` dan tanpa URL publik:

| Jenis | Lokasi |
|---|---|
| Audio | `storage/app/private/songs/{id_listener}/` |
| Foto artis | `storage/app/private/artists/{id_listener}/` |
| Cover album | `storage/app/private/albums/{id_listener}/` |
| Cover playlist | `storage/app/private/playlists/{id_listener}/` |

- Nama file dibuat acak oleh server, bukan dari nama file pengguna.
- Format audio: MP3, WAV, OGG, FLAC, M4A, AAC. Ekstensi **dan isi file** divalidasi di server. Batas default 50 MB.
- Format gambar: JPG, PNG, WEBP, maksimal 2 MB.
- Gambar dilayani lewat `/media/...` dan audio lewat `/music/{song}/stream` dan `/music/{song}/download`. Semuanya diotorisasi oleh Policy.
- Jalur file tidak pernah dikirim ke browser (`audio_path`, `photo`, `cover_image` disembunyikan; yang dikirim hanya URL berotorisasi).
- Folder `storage/app/private` harus dibackup bersama database.

## Cara menjalankan

Mode pengembangan memakai dua terminal:

```bash
# Terminal 1: backend
php artisan serve

# Terminal 2: frontend (Vite)
npm run dev
```

Buka `http://127.0.0.1:8000`.

Build aset untuk produksi:

```bash
npm run build
```

Panduan deployment lengkap (web server, SSL, backup, rollback) akan tersedia di `DEPLOYMENT.md` pada tahap dokumentasi berikutnya.

## Hak akses

| Kemampuan | Superadmin | Listener |
|---|:---:|:---:|
| Kelola listener (tambah, ubah, hapus, aktif/nonaktif) | Ya | Tidak |
| Lihat semua lagu semua listener | Ya | Tidak (hanya miliknya) |
| Putar dan unduh lagu | Semua lagu | Lagu miliknya |
| Tambah, ubah, hapus lagu | Tidak | Ya (miliknya) |
| Kelola album, artis, playlist | Tidak | Ya (miliknya) |
| Favorite | Tidak | Ya |
| Profil sendiri | Ya (email terkunci) | Ya |

Seluruh otorisasi dilakukan di **backend** (middleware role dan Policy). Menyembunyikan menu di frontend bukan satu-satunya pembatas.

Setelah login: superadmin diarahkan ke `/admin/dashboard`, listener ke `/home`. Akun nonaktif tidak dapat login, dan akun yang dinonaktifkan saat sedang login dikeluarkan pada request berikutnya.

## Pemutar musik

Satu instance audio global hidup di Pinia store (`resources/js/Stores/player.js`) dan dipasang di akar aplikasi, sehingga musik tidak berhenti saat berpindah halaman Inertia dan tidak mungkin ada dua audio berjalan bersamaan.

- **Kontrol:** previous, play/pause, next, stop, shuffle, repeat, seek, volume, dan mute.
- **Previous:** mengulang lagu dari awal jika sudah berjalan lebih dari 3 detik, jika tidak pindah ke lagu sebelumnya.
- **Shuffle:** lagu yang sedang diputar menjadi yang pertama dan sisanya diacak. Tidak ada lagu yang diulang sebelum semua lagu di antrean terputar. Pada repeat semua, urutan diacak ulang tanpa lagu yang baru selesai berada di urutan pertama.
- **Repeat:** Off, lalu Semua, lalu Satu, lalu kembali Off. Pada repeat satu, tombol next tetap pindah ke lagu berikutnya. Pada repeat off, pemutaran berhenti setelah lagu terakhir.
- **Antrean:** berisi daftar lagu dari tempat Anda menekan play (seluruh hasil filter My Songs, seluruh favorit, isi album, artis, atau playlist), maksimal **500 lagu**. Panel "Antrean" menampilkan urutan putar sebenarnya dan bisa diklik untuk melompat.
- **Panel "Sedang Diputar":** vinyl besar dengan cover album, judul, artis, dan album.
- **Lagu gagal dimuat:** ditandai error lalu dilewati ke lagu berikutnya.
- **Otomatis berhenti** saat logout, sesi habis, akun dinonaktifkan, atau lagu yang sedang diputar dihapus.
- Volume, mute, shuffle, dan repeat disimpan di browser (`localStorage`).
- Tombol media keyboard dan kontrol layar kunci didukung lewat Media Session API.
- **Recently Played** dicatat untuk lagu milik listener yang memutarnya. Pemutaran oleh superadmin tidak mengubahnya.

## Endpoint utama

| Metode dan URL | Keterangan |
|---|---|
| `GET /login`, `POST /login`, `POST /logout` | Autentikasi (login dibatasi percobaan) |
| `GET /home` | Beranda listener |
| `/artists`, `/albums`, `/songs`, `/playlists` | CRUD koleksi listener |
| `GET /artists/{id}`, `GET /albums/{id}` | Halaman detail |
| `GET /favorites`, `POST /songs/{id}/favorite` | Daftar dan toggle favorit |
| `POST /songs/{id}/played` | Mencatat Recently Played |
| `GET /queue/songs`, `/queue/favorites`, `/queue/albums/{id}`, `/queue/artists/{id}`, `/queue/playlists/{id}` | Antrean pemutar (JSON) |
| `GET /music/{song}/stream` | Streaming audio (mendukung HTTP Range) |
| `GET /music/{song}/download` | Unduh audio |
| `GET /media/artists/{id}/photo`, `/media/albums/{id}/cover`, `/media/playlists/{id}/cover` | Gambar privat |
| `GET /profile`, `PUT /profile`, `PUT /profile/password` | Profil sendiri |
| `GET /admin/dashboard` | Dashboard superadmin |
| `/admin/listeners`, `PATCH /admin/listeners/{id}/status` | Kelola listener |
| `GET /admin/songs`, `GET /admin/songs/{id}`, `GET /admin/queue/songs` | Pemantauan musik (hanya baca) |

Daftar lengkap: `php artisan route:list --except-vendor`.

## Keamanan

Yang sudah diterapkan:

- Autentikasi session, remember me, password di-hash, dan proteksi CSRF.
- **Rate limiting:** login (5 percobaan per menit untuk kombinasi email dan IP), streaming, unduh, favorit, pencatatan putar, antrean, dan ganti password.
- Role middleware (`role:superadmin`, `role:listener`), middleware status akun aktif, dan Policy per model.
- Validasi kepemilikan di backend. `user_id` tidak pernah dipercaya dari frontend, dan `role` serta `is_active` tidak dapat diisi lewat mass assignment.
- Penyimpanan privat, streaming dan unduh berotorisasi, serta `X-Content-Type-Options: nosniff`.
- Validasi file audio berdasarkan ekstensi dan isi file, dan validasi gambar.
- Output di frontend di-escape oleh Vue.
- Sesi pengguna dicabut saat akun dinonaktifkan atau passwordnya diganti.

Tinjauan keamanan akhir (security review) belum dilakukan.

## Pengujian

Pemeriksaan otomatis berikut memakai data uji yang di-rollback (atau file uji yang dibersihkan):

| Perintah | Yang diperiksa |
|---|---|
| `php artisan bs7:db-check` | Koneksi, tabel, relasi, constraint unik, cascade |
| `php artisan bs7:auth-check` | Rute, alias middleware, matriks Policy |
| `php artisan bs7:media-check` | Penyimpanan privat dan Policy media |
| `php artisan bs7:song-check` | Validasi audio, streaming Range (206 dan 416), unduh, Policy lagu |
| `php artisan bs7:playlist-check` | Playlist (urutan, duplikat), favorit, cascade |
| `php artisan bs7:home-check` | Beranda, pencarian, antrean, urutan album, Recently Played |
| `php artisan bs7:admin-check` | Superadmin, kelola listener, hapus listener beserta file |
| `node scripts/player-check.mjs` | Logika shuffle, repeat, antrean, previous/next (tanpa browser) |

Setiap perintah berakhir dengan `SEMUA PENGECEKAN LULUS` bila semuanya berhasil.

Perintah bantu untuk pengujian manual (`bs7:dev-fill`, `bs7:dev-songs`, `bs7:dev-listeners`) membuat atau menghapus data uji. Perintah ini **dinonaktifkan di production**. `bs7:create-listener` tetap tersedia.

> Tes otomatis PHPUnit (`php artisan test`) belum ditulis. Tes contoh bawaan Laravel belum disesuaikan dan dapat gagal pada aplikasi ini.

## Struktur proyek

```
app/
├── Console/Commands/        # perintah bs7:* (pemeriksaan dan alat bantu)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # Dashboard, Listener, UploadedMusic, MusicQueue
│   │   ├── Auth/            # AuthenticatedSession
│   │   ├── Listener/        # Home, Artist, Album, Song, Playlist, Favorite, Queue, dll.
│   │   ├── Music/           # Media (gambar privat), Music (stream dan unduh)
│   │   └── ProfileController.php
│   ├── Middleware/          # RoleMiddleware, EnsureUserIsActive, HandleInertiaRequests
│   └── Requests/            # Form Request (termasuk Auth/LoginRequest)
├── Models/                  # User, Artist, Album, Song, Playlist, Favorite
├── Policies/                # ListenerOwned, Song, Artist, Album, Playlist, User
├── Rules/AudioFile.php
├── Services/                # Artist, Album, Song, Playlist, Favorite, FileUpload,
│                            # MusicStream, Library, Listener, Admin
└── Support/AudioFormats.php
bootstrap/app.php            # middleware, alias, penanganan error
config/broseven.php          # pengaturan aplikasi (admin, disk media, batas audio)
database/                    # migration, factory, SuperAdminSeeder
resources/
├── css/                     # app, variables, layout, sidebar, components, vinyl, responsive
├── js/
│   ├── app.js, axios.js
│   ├── Components/          # Sidebar, MusicPlayer, VinylRecord, SongList, SingleSelect, dll.
│   ├── Composables/         # useDebounce, useFavorite, useFormat, useAudioMeta, usePlayCollection
│   ├── Layouts/             # ListenerLayout, AdminLayout
│   ├── Pages/               # Auth, Listener, Admin, Error
│   └── Stores/              # player.js, toast.js
└── views/app.blade.php
routes/web.php
scripts/player-check.mjs
PROGRESS.md
```

`resources/js/Components/Header.vue` adalah sisa tahap awal dan sudah tidak dipakai.

## Keterbatasan dan Rencana

- **Lirik lagu belum diimplementasikan** (tidak ada input lirik, panel lirik, maupun integrasi API). Kolom `songs.lyrics` sudah disiapkan di database tetapi belum dipakai.
- **Tes otomatis PHPUnit belum ada.** Pemeriksaan memakai perintah `bs7:*-check`.
- Memuat ulang halaman (F5) menghentikan musik. Yang dijaga adalah perpindahan antarhalaman.
- Antrean dibatasi 500 lagu per permintaan putar.
- Durasi lagu dibaca di browser saat unggah. Untuk sebagian format durasi bisa kosong (lagu tetap tersimpan dan dapat diputar).
- Tidak ada registrasi publik, reset password lewat email, atau verifikasi email. Listener dibuat oleh superadmin.
- `php artisan serve` memproses satu permintaan pada satu waktu sehingga pemutaran file besar dapat terasa lambat saat dev. Gunakan Apache atau Nginx (misalnya lewat Laragon) bila perlu.
- Dokumen yang masih direncanakan: `DEPLOYMENT.md`, `CHANGELOG.md`, `LICENSE`, dan finalisasi `.env.example`.

## Pemecahan masalah

| Masalah | Solusi |
|---|---|
| Upload lagu gagal atau semua isian dianggap kosong | Batas upload PHP terlalu kecil. Lihat [Batas upload PHP](#batas-upload-php), lalu restart server PHP. |
| `Vite manifest not found` | Jalankan `npm run dev` atau `npm run build`. |
| Halaman putih | Buka Console browser (F12). Pastikan `npm run dev` berjalan dan `npm install` sudah dijalankan. |
| `could not find driver` | Aktifkan ekstensi `pdo_mysql` di `php.ini`. |
| `Access denied for user` atau `Unknown database` | Periksa `DB_*` di `.env`, pastikan database sudah dibuat, lalu `php artisan config:clear`. |
| Seeder: `ADMIN_NAME ... wajib diisi` | Isi `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` di `.env`, lalu `php artisan config:clear`. |
| Perubahan `.env` tidak terbaca | `php artisan config:clear` |
| Audio tidak terputar atau seek tidak berfungsi | Buka `/music/{id}/stream` di Network. Respons seek yang benar berstatus 206. 403 berarti bukan pemilik, 404 berarti file tidak ada. |
| 419 saat mengirim form | Sesi atau CSRF kedaluwarsa. Muat ulang halaman lalu coba lagi. |

## Lisensi

Belum ditentukan. File `LICENSE` akan ditambahkan pada tahap persiapan GitHub.