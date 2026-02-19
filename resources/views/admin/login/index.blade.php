<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система учета времени - Вход</title>
    <link rel="stylesheet" href="{{asset('css/style.css')}}">
</head>
<body>

<div class="login-container">
    <div class="header">
        <img src="{{asset('images/logo.png')}}" alt="TimeFlow">
    </div>

    <div class="login-form">
        <!-- Общие ошибки (если есть) -->
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.process') }}">
            @csrf
            <div class="form-group {{ $errors->has('name') ? 'error' : '' }}">
                <svg width="28" height="28" viewBox="0 0 39 39" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <rect width="38.1124" height="38.1124" fill="url(#pattern0_4_79)" fill-opacity="0.5"/>
                    <defs>
                        <pattern id="pattern0_4_79" patternContentUnits="objectBoundingBox" width="1" height="1">
                            <use xlink:href="#image0_4_79" transform="scale(0.01)"/>
                        </pattern>
                        <image id="image0_4_79" width="100" height="100" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
                    </defs>
                </svg>
                <input value="{{ old('name') }}" name="name" type="text" id="login" class="form-control" placeholder="ЛОГИН">
                @if($errors->has('name'))
                    <div class="error-message">{{ $errors->first('name') }}</div>
                @endif
            </div>

            <div class="form-group {{ $errors->has('password') ? 'error' : '' }}">
                <svg width="25" height="25" viewBox="0 0 33 33" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <rect width="32.6678" height="32.6678" fill="url(#pattern0_4_67)" fill-opacity="0.5"/>
                    <defs>
                        <pattern id="pattern0_4_67" patternContentUnits="objectBoundingBox" width="1" height="1">
                            <use xlink:href="#image0_4_67" transform="scale(0.01)"/>
                        </pattern>
                        <image id="image0_4_67" width="100" height="100" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
                    </defs>
                </svg>
                <div class="password-wrapper">
                    <input name="password" type="password" id="password" class="form-control" placeholder="ПАРОЛЬ">
                    <button type="button" class="show-password" onclick="togglePassword()">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 4C7 4 2.73 7.11 1 11.5C2.73 15.89 7 19 12 19C17 19 21.73 15.89 23 11.5C21.73 7.11 17 4 12 4ZM12 16.5C9.24 16.5 7 14.26 7 11.5C7 8.74 9.24 6.5 12 6.5C14.76 6.5 17 8.74 17 11.5C17 14.26 14.76 16.5 12 16.5ZM12 8.5C10.34 8.5 9 9.84 9 11.5C9 13.16 10.34 14.5 12 14.5C13.66 14.5 15 13.16 15 11.5C15 9.84 13.66 8.5 12 8.5Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
                @if($errors->has('password'))
                    <div class="error-message">{{ $errors->first('password') }}</div>
                @endif
            </div>
            <button type="submit" class="btn btn-primary">Войти</button>
        </form>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const showPasswordBtn = document.querySelector('.show-password');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            showPasswordBtn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 7C14.76 7 17 9.24 17 12C17 12.65 16.87 13.26 16.64 13.83L19.56 16.75C21.07 15.49 22.26 13.86 22.99 12C21.26 7.61 16.99 4.5 11.99 4.5C10.59 4.5 9.25 4.75 8.01 5.2L10.17 7.36C10.74 7.13 11.35 7 12 7ZM2 4.27L4.28 6.55L4.74 7.01C3.08 8.3 1.78 10.02 1 12C2.73 16.39 7 19.5 12 19.5C13.55 19.5 15.03 19.2 16.38 18.66L16.8 19.08L19.73 22L21 20.73L3.27 3L2 4.27ZM7.53 9.8L9.08 11.35C9.03 11.56 9 11.78 9 12C9 13.66 10.34 15 12 15C12.22 15 12.44 14.97 12.65 14.92L14.2 16.47C13.53 16.8 12.79 17 12 17C9.24 17 7 14.76 7 12C7 11.21 7.2 10.47 7.53 9.8ZM11.84 9.02L14.99 12.17L15.01 12.01C15.01 10.35 13.67 9.01 12.01 9.01L11.84 9.02Z" fill="currentColor"/>
            </svg>`;
        } else {
            passwordInput.type = 'password';
            showPasswordBtn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 4C7 4 2.73 7.11 1 11.5C2.73 15.89 7 19 12 19C17 19 21.73 15.89 23 11.5C21.73 7.11 17 4 12 4ZM12 16.5C9.24 16.5 7 14.26 7 11.5C7 8.74 9.24 6.5 12 6.5C14.76 6.5 17 8.74 17 11.5C17 14.26 14.76 16.5 12 16.5ZM12 8.5C10.34 8.5 9 9.84 9 11.5C9 13.16 10.34 14.5 12 14.5C13.66 14.5 15 13.16 15 11.5C15 9.84 13.66 8.5 12 8.5Z" fill="currentColor"/>
            </svg>`;
        }
    }
</script>

</body>
</html>
