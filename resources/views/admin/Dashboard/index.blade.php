<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Статистика</title>
    <link href="{{asset('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('css/dashboard.css')}}">
    <link rel="stylesheet" href="{{asset('css/main.css')}}">
    <link rel="stylesheet" href="{{asset('css/dashboard.css')}}">
    <link rel="stylesheet" href=" {{asset('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css')}}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <!-- ПОДКЛЮЧИТЕ Chart.js ЗДЕСЬ -->
    <script src=" {{asset('https://cdn.jsdelivr.net/npm/chart.js')}}" defer></script>
    <style>
        /* Добавьте эти стили для контейнера графика */
        .chart-canvas-container {
            width: 100%;
            height: 400px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
<!-- HEADER -->
<header class="header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
        <img src="{{asset('images/logo.png')}}" alt="TimeFlow" width="122" height="82">
    </div>

    <div class="persons d-flex align-items-center gap-3 p-4">
        <div class="text-end">
            <div class="fw-medium">{{ Session::get('admin_name') }}</div>
        </div>
        <div class="logo-circle d-flex align-items-center justify-content-center">
            @php
                $name = Session::get('admin_name');
                $initials = '';
                $nameParts = explode(' ', $name);
                foreach ($nameParts as $part) {
                    if (!empty($part)) {
                        $initials .= mb_substr($part, 0, 1);
                    }
                }
                 $initials = mb_strtoupper($initials);
            @endphp
            {{ $initials }}
        </div>
    </div>
</header>

<!-- TABS -->
<nav class="tabs-container">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link" type="button" href="{{ route('admin.main.index') }}">Табель</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" type="button" href="{{route('admin.dashboard.index')}}">Статистика</a>
        </li>
    </ul>
</nav>

<!-- ОСНОВНОЙ КОНТЕНТ -->
<main class="main-content">
    <div class="mb-4">
        <h1 class="page-title">Детальная статистика</h1>
        <p class="page-subtitle">Аналитика рабочего времени и производительности</p>
    </div>

    <!-- STATISTICS CARDS -->
    <div class="stats-grid">
        <div class="stat-card">

            <div class="stat-content">
                <div class="stat-number">42</div>
                <div class="stat-title">Сотрудников не работают</div>
            </div>
            <div class="stat-icon">
                <svg width="15" height="15" viewBox="0 0 14 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M-8.87513e-05 5.14206L1.07946 4.0341L4.82946 7.72729L12.5851 1.3113e-05L13.6931 1.10797L4.82946 9.9432L-8.87513e-05 5.14206Z" fill="#4CAF5A"/>
                </svg>

            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number">4</div>
                <div class="stat-title">Отсутствуют</div>
            </div>
            <div class="stat-icon">
                <svg width="15" height="15" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1.10791 9.80114L-4.42266e-05 8.69318L8.72155 0L9.8295 1.10795L1.10791 9.80114ZM8.72155 9.80114L-4.42266e-05 1.10795L1.10791 0L9.8295 8.69318L8.72155 9.80114Z" fill="#F44359"/>
                </svg>

            </div>
        </div>

        <div class="stat-card">

            <div class="stat-content">
                <div class="stat-number">3</div>
                <div class="stat-title">Опоздали</div>
            </div>
            <div class="stat-icon">
                <svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <rect width="19" height="19" fill="url(#pattern0_189_21)"/>
                    <defs>
                        <pattern id="pattern0_189_21" patternContentUnits="objectBoundingBox" width="1" height="1">
                            <use xlink:href="#image0_189_21" transform="scale(0.01)"/>
                        </pattern>
                        <image id="image0_189_21" width="100" height="100" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
                    </defs>
                </svg>

            </div>
        </div>

        <div class="stat-card">

            <div class="stat-content">
                <div class="stat-number">12</div>
                <div class="stat-title">Сверхурочно</div>
            </div>
            <div class="stat-icon">
                <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <rect width="21" height="21" fill="url(#pattern0_189_22)"/>
                    <defs>
                        <pattern id="pattern0_189_22" patternContentUnits="objectBoundingBox" width="1" height="1">
                            <use xlink:href="#image0_189_22" transform="scale(0.01)"/>
                        </pattern>
                        <image id="image0_189_22" width="100" height="100" preserveAspectRatio="none" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAAMX0lEQVR4AexdeZAcVRn/fb1zbMJV8SqFYIGCATkDKgRICIJ/CSJYKazdmZUjLocgoQolsLPSxU5CYqlEoYokVArMzqagYpVlEf8BOTQkoGg4xCMcQpXggRYikmRneqY/v7eTnXk9MzvbPTPdPZvtV/N6vvfe9953/Pqd3bNrIApd5YEIkK6CA/AMSCpT4Ci694FXvD0D4lVAxO/NAxEg3vzlO3cEiO8u9iagbUBy2QRFseoDb+6v524bkPomo5x2PBAB0o73fKgbAeKDU9tp0jMgDP6fLnBgaO8RenqCnqWXfnPvfN30Wl/pZVPRngGBjT/qjTF61kegAAoMKvWs130j9EsSPX08A2IYNKpLYKILbYq9Odt371SM/RVMX9R9YxBt1tNuaM+AjMfi90nDuyRGn2YesPnZOf+Mb2rG0qjMMyBbTSrAjl8oQ9fvGjUY5UFcw78FEhdv3EiWV394BkQJyK2mv8/9d3yR0NfJxLUTzO8LPbs/4gPlCyJce/DbibOUj1pxSEuAKEEKfdmh3zuWTZ6dW5U8RGjHjl3x6LG2fKaldVsUXae/+ED5YnQksV75RvG0ElsGpBVhUZ3pPRABMr2PAuWIAAnU3dMLm0mATG/NAcARAdJlIEaARIB0mQe6TJ2oh0SAdJkHukydqIdEgHSZB7pMnaiHzBZAmNnWbTVNnrHgL1vGPbotzE7b9LJ2aT+d9J6u3BvAoXq6a2g3ipyAw3Q2cdq7erqTtLTdyea0toj+o6XApcKMfRkiUaO7PLZ22Kbb2S7tGyDE/CdduSKwUE/PJJpATt2J/+yX/r4BAgPPQwvM9AUtObNIhkN3YjhsQweDf4Cw8aiupwG+aNlNPEfPmwn0oMlzIbrrupbYeERPd5L2DZD87th2wP5XVVmalzjYuqKanhnUvqLSmSqTOjPetl6O7fBLe98A2bqVSjL53e9Q3ObhZbdwxThHWRcm+k0+1Abd6lCNeJOyzZHXwYRvgCgde+zSj8BcULSKRPTRZDy/VtEzIZJlfZ/A2uqQ86VY8R4/dfcVkM2r5r4l28F1TgOMq/uHC1c687ovlRouLAdhua4ZEe560Dzob3pep2lfAVHKGvsSdzBD9oWoBLJ5Q/9wvq+S0WWE6JaSnu14T5eB1+f0JEb8VtV3QEa/R3t6DFwK8L6KMUQxYuRSmfya2mOJCk8oBFMqY90CGz8GqHJcwszjIFy20aS9cBda5vIdEKXZ5pHEcyC6RgyzVbociQC6Jbkg/2TKHD8GIQelg9wgv5IbZw0RVfyidDZAg2MjiWeDUNEIQoiSkRtJbIaBtAwFsmlXOZPROAdFelHdmeH0FukVw4VBWPQcILpMqjXxzSXDoKtGVyVGJ5IBXAIDRNkyNpLcwtRziRyrvKPS1UiyYeQ1QfeWSq9gbADRwVV9gAkd7Z6LR0cSDyDAECggyq6xbGxbT9w+HbBl46hy9Gicwxa90D9UWGH6eFyv2lYylKz6XqH0sbcrHXOrYz9XqSBj4IAo4x4w57yRyybPBeFqGcIcb87L+D2XZHn5SrHwVCozvkDxdzL2meOfeLVoPaZkEJEci+itq4UHrczvTp6ndNRLgqJDAaRsHLHMKxuJ7JNlpn+8nFe9EmgRQM91bm4pzxWG9EAAS1EX7B1GiU/NZeNr/dyJ14mtyQgRkLImo9k5r2/Jxi9o1FsAmphbEgsK29vpLapXCLCPo8FcgYnlOK08JpZcsvnO3pcRcggdkLL95d5ix/kUST8p0fFRvYWZdqUyhRvV+O8obJJQvKoOWfR7YWvQK/CkHeMTVa8wTZKOKlwhf7oEkLIXtpi9fxHnfF71Fq75+fX+8X6dzC3b+zLjx5ZrTH293Nx31GuW9QvhWLe/rpDlj+wtZIOnekX8fCWznNsd164CpDtcEq4WTQEJWjV9rJdh6hBdfvmuxopjY4nFW7K9r+hljWi1SvpkXOYmYMX+uhW2co/hNWq1pWRWCrqA6BJAmq+AZPjaScSn5bKJH5oexnrFq+oYZJ9IjCca+HupUaSXUnJ+peabBuWBZ4UOSDqz7+j0kPUYmqyACrsTS3LZ3t2temdUVnKjq+Lnq7mpdt+D/Ss5NTe1s5JDh0KIgJR7BbPxIhPOq7WHwTsBXiiTfIf2BeWVHMm+p1FvkSHyLHR034OWQiiAqBVQKpP/ZaNeocZ7Ztyk5op2esVU3lC9Rc0tSoaS5eQr73uSC/JPKB2dZcGkAgcklRn/UrFo7AKMxfUm2k9RnE8ZW5VYZ5r+7QtU20qGkgXYT9XrYSxWOqaHxi+qL/M3J1BA0pnCVcz0U4DmwREqZ0hLc2bvq44iHxNKVi6bXIIGZ2oQHZnoZ/K4+ToEGAIDRE5Xv8bg+4iqD3/KdsodGuOTOzdXlFt1fy3PLYjzwvreQgSb75bn6wPu22uPMxBABIwziXgDIAZiMjADvFZOVgPtFZPSa78rvQW0UuaWyjEKkdxAzBv7hgqfq63jR9p3QNI380EgPARQEpOBuUiEPhkuVoZ5sjqpTvVbeouc9tY/2aQkAQ9N2FJl9oXyHRDuLdwuxny8qj1ABn19dCT5IDoUVA9MDVmybO1Mg+rJJhFdo7dGhKPElmE9zw/aV0AG1N9jZNyoK85srx/twGPRpSbH+ofzfelM/tfirKdBvCM1VNgl4/3yG27gam/UhXugR7OJTXIjqT/WVq3FuOmr5p7Dqxmdp3wFxDZ6VoAoMak2M/+jUEyunEy3+j2QsS6Yb1m7iGmMQdWxnbAQjPvePcx6RYAZVKC1KkPVs2Pxm6X9txQ9EcWWnlLMcYNN5Hfw4hsgE86w+XJdVyK6Y+ta+q+e54UeuC1/cn8mv8MGPwrCSVPVZeBIAWbD/JL1QnrYOncqvunyx0x6zwDfqfMR8xV+vh3jGyCH28XFIONDk8aIIe/k98QfmEx7+V6qhqeMNWQTniWQ+7mC8Wlm+wlZId29zGTHWyVu5c+Jxe9nZu0nbMaHe48rnuO2vlc+3wAx2L5AV4aJHt56F1XfXtQLm9AyRxw/v1DYSeAsZMhowjpFEckaAtcnitaLfUPWkimYpsxWbysS0TYHA9uOH/A4ytpM+AYIbMhGS9OOZZjRkm5IAeNS2a38BgZ91g1/Mx4CjjZgPyYrshXN+BqVEeMRPZ8Jp8Kn4BsgTHScrnOPgefhIaQz1q0M/ATkfIHNQxP1rEQxItzVPzR+r2mya9ttg526s9O2ekGt57hWyrMI5nl6HepJVFcrekEdLcfymfx35ZhlNQQN+BCIjGteK1pb1NzkpvlCne78ATf1WuHxDxDgUF2howDH79b1Mp1OZwojAH0LPgcGLju8aG0CWEazaYT9AY6VoQyjvv0KzDdAiMjRtmlOf5wue4frGTQ0jXsaFreSKQoOpDIFx7K2UTu1xzu1tjWq02qe6NRq1c7Wk8n2TFle/qCzrbpq7dv9Q/lLXHEGwNQVgAzcyh8kpq0Eigdgc40Iud8Jm9K373Wct9UwBZbsCkBsw1oDgx3/eyMwD0wIonklOx5G75yQrl9CByQ9XDiDwVfqSoVBG8xfSX3H8m3D59am0AEpMVbLoBG6HsphbNum+g4zhuoIOT4/yQDXvQIUlkNkDjtrYNhaFJZ8JTdUQMimbwJE6KJQYr42THVCA6R8hG1/OUzjG8mWQ8wL3e7gG9VvNy80QHo/VVwE7Xi+XUM6V5/mHVkqnt259ry1FBogDHg+CvdmWuvcNmP/847W22i1ZmiAgPj4VpX2vx53/MembnUODRB5DOs4nnercBB8jPBulpYBkUeiCXUY2H9b/hl5zv2+HNKxHmsdp5cp2gB9ppanW9JKN6WjHmt108sUXfZB/mk5rf6G8k0tv9u04ZZR51OvwiQt6xkw7iaDziDQQXr5bKTLPqAzZW68J1m0nk7dxh9rxQ+eAVHox6z4NhCcj2hbkX7g1jkNsB4eHGTPh6WeAUmWrEFEYEx/Kxk4fe9HrOXTMzo5PAPCJU45miB6uBizjshlE47/Yzjb0gYX5xPzNt03sjhI62k3tGdAiHCC3rBhW9c+6POfvdPldSut/pyhHS/VHruc6FVfz4Cg5i0QpYhXoQcq/5g5903dNpnoHT/t1sumor0DMlVLUb5rDzRjjABp5p0QyiJAQnB6M5FtA6J2qVEsVE4pmjnbTVnbgLgREvG490AEiHtfBcIZARKIm90L8QzIbNuBt2uveyjKnJ4BKVeLrn554P8AAAD//5ZsMNQAAAAGSURBVAMAyZbFMjrjfCsAAAAASUVORK5CYII="/>
                    </defs>
                </svg>

            </div>
        </div>

    </div>

    <div class="charts-container">
        <div class="chart-header">
            <h3 class="chart-title">Аналитика рабочего времени</h3>
            <div class="time-period">
                <button class="period-btn active">За день</button>
                <button class="period-btn">За неделю</button>
                <button class="period-btn">За месяц</button>
                <button class="period-btn">За год</button>
            </div>
        </div>

        <div class="chart-canvas-container"><canvas id="workHoursChart"></canvas></div>
    </div>


</main>

<!-- JavaScript -->
<script src="{{asset('js/dashboard.js')}}"></script>

<footer>
    © 2026 Табель - Система учета рабочего времени. Все права защищены.
</footer>
</body>
</html>
