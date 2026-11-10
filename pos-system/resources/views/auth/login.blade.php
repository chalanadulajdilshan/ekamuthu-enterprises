<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ $settings['store_name'] }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login-page">
    <form class="login-card" method="post" action="{{ url('/login') }}">
        @csrf
        <div class="brand">
            <div class="brand-mark"><i class="fa-solid fa-code"></i></div>
            <div class="brand-text"><b>SOURCE CODE</b><small>THE EYE OF TECHNOLOGY</small></div>
        </div>
        <h2 style="margin:0 0 4px;text-align:center">{{ $settings['store_name'] }} POS</h2>
        <p class="muted" style="text-align:center;margin:0 0 22px">Sign in to start selling</p>

        @if ($errors->any())
            <div class="alert alert-err">{{ $errors->first() }}</div>
        @endif

        <div class="field">
            <label class="lbl" for="email">Email</label>
            <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        <div class="field">
            <label class="lbl" for="password">Password</label>
            <input class="input" id="password" type="password" name="password" required autocomplete="current-password">
        </div>
        <div class="field">
            <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
        </div>
        <button class="btn btn-primary" style="width:100%;height:46px;font-size:16px"><i class="fa-solid fa-right-to-bracket"></i> Sign in</button>
    </form>
</div>
</body>
</html>
