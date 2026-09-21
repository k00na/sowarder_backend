<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set password — {{ $restaurant?->name ?? 'Sowarder' }}</title>
    <style>
        :root { color-scheme: light; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; background: #f8fafc; color: #0f172a; }
        main { max-width: 28rem; margin: 4rem auto; padding: 2rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        p { color: #475569; margin: 0 0 1.25rem; line-height: 1.5; }
        label { display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.35rem; }
        input { width: 100%; box-sizing: border-box; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; margin-bottom: 1rem; }
        button { width: 100%; padding: 0.7rem 1rem; border: 0; border-radius: 0.5rem; background: #0f766e; color: #fff; font-weight: 600; cursor: pointer; }
        .error { color: #b91c1c; font-size: 0.875rem; margin: -0.5rem 0 0.75rem; }
    </style>
</head>
<body>
<main>
    <h1>Set your admin password</h1>
    <p>
        {{ $restaurant?->name ?? 'Your restaurant' }} —
        {{ $user->email }}
    </p>

    <form method="POST" action="{{ request()->fullUrl() }}">
        @csrf

        <label for="password">Password</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

        <button type="submit">Save password and continue</button>
    </form>
</main>
</body>
</html>
