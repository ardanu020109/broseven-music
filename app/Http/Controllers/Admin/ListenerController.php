<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListenerRequest;
use App\Http\Requests\UpdateListenerRequest;
use App\Models\User;
use App\Services\ListenerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ListenerController extends Controller
{
    public function __construct(private ListenerService $listeners)
    {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = $this->text($request->query('search'));
        $raw    = $request->query('status');
        $status = in_array($raw, ['active', 'inactive'], true) ? $raw : null;

        return Inertia::render('Admin/Listeners/Index', [
            'listeners' => $this->listeners->paginate(['search' => $search, 'status' => $status])->withQueryString(),
            'filters'   => ['search' => $search ?? '', 'status' => $status ?? ''],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Admin/Listeners/Create');
    }

    public function store(StoreListenerRequest $request): RedirectResponse
    {
        $listener = $this->listeners->create($request->validated());

        return redirect()->route('admin.listeners.show', $listener)->with('status', 'Listener berhasil ditambahkan.');
    }

    public function show(User $listener): Response
    {
        Gate::authorize('view', $listener);

        return Inertia::render('Admin/Listeners/Show', [
            'listener' => $listener->only(['id', 'name', 'email', 'is_active', 'created_at', 'last_login_at']),
            'stats'    => $this->listeners->stats($listener),
            'songs'    => $this->listeners->recentSongs($listener),
        ]);
    }

    public function edit(User $listener): Response
    {
        Gate::authorize('update', $listener);

        return Inertia::render('Admin/Listeners/Edit', [
            'listener' => $listener->only(['id', 'name', 'email', 'is_active']),
        ]);
    }

    public function update(UpdateListenerRequest $request, User $listener): RedirectResponse
    {
        $this->listeners->update($listener, $request->validated());

        return redirect()->route('admin.listeners.show', $listener)->with('status', 'Listener berhasil diperbarui.');
    }

    public function destroy(User $listener): RedirectResponse
    {
        Gate::authorize('delete', $listener);

        $this->listeners->delete($listener);

        return redirect()->route('admin.listeners.index')->with('status', 'Listener dan seluruh datanya berhasil dihapus.');
    }

    public function status(Request $request, User $listener): RedirectResponse
    {
        Gate::authorize('update', $listener);

        $request->validate(['is_active' => ['required', 'boolean']]);

        $active = $request->boolean('is_active');
        $this->listeners->setActive($listener, $active);

        return back()->with('status', $active ? 'Listener diaktifkan.' : 'Listener dinonaktifkan dan dikeluarkan dari semua perangkat.');
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = mb_substr(trim($value), 0, 100);

        return $value !== '' ? $value : null;
    }
}