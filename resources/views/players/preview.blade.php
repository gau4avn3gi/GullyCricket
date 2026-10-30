<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <title>Gully Cricket · Squad</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-950 antialiased dark:bg-slate-950 dark:text-slate-50">
        <main class="mx-auto flex w-full max-w-3xl flex-col gap-8 px-5 py-10 sm:px-8">
            <header class="flex items-end justify-between gap-4">
                <div class="flex flex-col gap-2">
                    <p class="text-sm font-bold tracking-widest text-emerald-700 dark:text-emerald-300">GULLY CRICKET</p>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Your squad</h1>
                </div>
                <div class="rounded-2xl bg-emerald-100 px-4 py-3 text-right dark:bg-emerald-950">
                    <p class="text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ $players->count() }}</p>
                    <p class="text-xs text-emerald-800/80 dark:text-emerald-200/80">players</p>
                </div>
            </header>

            <p class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                Browser preview uses Laravel’s local database. The mobile app stores its roster in private device storage, so records don’t sync between them.
            </p>

            @if (session('status'))
                <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                    {{ session('status') }}
                </p>
            @endif

            <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-lg font-semibold">Add a player</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Add a name and an optional cricket role.</p>
                </div>

                <form method="POST" action="{{ route('players.preview.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <div class="flex flex-col gap-2">
                        <label for="name" class="text-sm font-medium">Player name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            maxlength="80"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950"
                            placeholder="e.g. Arjun Sharma"
                        >
                        @error('name')
                            <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="role" class="text-sm font-medium">Role <span class="font-normal text-slate-500">(optional)</span></label>
                        <input
                            id="role"
                            name="role"
                            type="text"
                            value="{{ old('role') }}"
                            maxlength="60"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950"
                            placeholder="Batter, bowler, all-rounder"
                        >
                        @error('role')
                            <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900 sm:w-fit">
                        Add to squad
                    </button>
                </form>
            </section>

            <section class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">Players</h2>
                    <span class="text-sm text-slate-600 dark:text-slate-400">Saved locally</span>
                </div>

                @forelse ($players as $player)
                    <article class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex min-w-0 flex-col gap-1">
                            <h3 class="truncate font-semibold">{{ $player->name }}</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $player->role ?: 'Player' }}</p>
                        </div>
                        <form method="POST" action="{{ route('players.preview.destroy', $player) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-600/30 dark:text-red-300 dark:hover:bg-red-950">
                                Remove
                            </button>
                        </form>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-5 py-10 text-center dark:border-slate-700">
                        <h3 class="font-semibold">Your roster starts here</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Add your first player above.</p>
                    </div>
                @endforelse
            </section>
        </main>
    </body>
</html>
