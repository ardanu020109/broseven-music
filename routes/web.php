<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ListenerController;
use App\Http\Controllers\Admin\MusicQueueController;
use App\Http\Controllers\Admin\UploadedMusicController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Listener\AlbumController;
use App\Http\Controllers\Listener\AlbumDetailController;
use App\Http\Controllers\Listener\ArtistController;
use App\Http\Controllers\Listener\ArtistDetailController;
use App\Http\Controllers\Listener\FavoriteController;
use App\Http\Controllers\Listener\HomeController;
use App\Http\Controllers\Listener\PlaylistController;
use App\Http\Controllers\Listener\PlaylistSongController;
use App\Http\Controllers\Listener\QueueController;
use App\Http\Controllers\Listener\SongController;
use App\Http\Controllers\Listener\SongPlayController;
use App\Http\Controllers\Music\MediaController;
use App\Http\Controllers\Music\MusicController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Pintu masuk: guest -> login, user -> beranda sesuai role
Route::get('/', function () {
    $user = auth()->user();

    return $user ? redirect()->to($user->homePath()) : redirect()->route('login');
});

// Hanya untuk guest
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// Logout
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Area terlindungi: login + akun aktif
Route::middleware(['auth', 'active'])->group(function () {

    // Profil milik sendiri (superadmin dan listener)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:10,1')
        ->name('profile.password');

    // Gambar privat (otorisasi oleh policy: pemilik atau superadmin)
    Route::get('/media/artists/{artist}/photo', [MediaController::class, 'artistPhoto'])->name('media.artist.photo');
    Route::get('/media/albums/{album}/cover', [MediaController::class, 'albumCover'])->name('media.album.cover');
    Route::get('/media/playlists/{playlist}/cover', [MediaController::class, 'playlistCover'])->name('media.playlist.cover');

    // Audio privat (otorisasi oleh SongPolicy: pemilik atau superadmin)
    Route::get('/music/{song}/stream', [MusicController::class, 'stream'])
        ->middleware('throttle:600,1')
        ->name('music.stream');
    Route::get('/music/{song}/download', [MusicController::class, 'download'])
        ->middleware('throttle:60,1')
        ->name('music.download');

    // LISTENER
    Route::middleware('role:listener')->group(function () {
        Route::get('/home', HomeController::class)->name('home');

        Route::resource('artists', ArtistController::class)->except(['show']);
        Route::get('/artists/{artist}', ArtistDetailController::class)->name('artists.show');

        Route::resource('albums', AlbumController::class)->except(['show']);
        Route::get('/albums/{album}', AlbumDetailController::class)->name('albums.show');

        Route::resource('songs', SongController::class)->except(['show']);
        Route::post('/songs/{song}/played', SongPlayController::class)
            ->middleware('throttle:120,1')
            ->name('songs.played');

        // Favorite
        Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
        Route::post('/songs/{song}/favorite', [FavoriteController::class, 'toggle'])
            ->middleware('throttle:120,1')
            ->name('songs.favorite');

        // Playlist
        Route::resource('playlists', PlaylistController::class);
        Route::post('/playlists/{playlist}/songs', [PlaylistSongController::class, 'store'])->name('playlists.songs.store');
        Route::put('/playlists/{playlist}/songs/reorder', [PlaylistSongController::class, 'reorder'])->name('playlists.songs.reorder');
        Route::delete('/playlists/{playlist}/songs/{song}', [PlaylistSongController::class, 'destroy'])->name('playlists.songs.destroy');

        // Antrean pemutar (JSON)
        Route::middleware('throttle:60,1')->prefix('queue')->name('queue.')->group(function () {
            Route::get('/songs', [QueueController::class, 'songs'])->name('songs');
            Route::get('/favorites', [QueueController::class, 'favorites'])->name('favorites');
            Route::get('/albums/{album}', [QueueController::class, 'album'])->name('albums');
            Route::get('/artists/{artist}', [QueueController::class, 'artist'])->name('artists');
            Route::get('/playlists/{playlist}', [QueueController::class, 'playlist'])->name('playlists');
        });
    });

    // SUPERADMIN
    Route::middleware('role:superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // Kelola listener
        Route::resource('listeners', ListenerController::class);
        Route::patch('/listeners/{listener}/status', [ListenerController::class, 'status'])->name('listeners.status');

        // Pemantauan musik (hanya baca)
        Route::get('/songs', [UploadedMusicController::class, 'index'])->name('songs.index');
        Route::get('/songs/{song}', [UploadedMusicController::class, 'show'])->name('songs.show');
        Route::get('/queue/songs', MusicQueueController::class)
            ->middleware('throttle:60,1')
            ->name('queue.songs');
    });
});