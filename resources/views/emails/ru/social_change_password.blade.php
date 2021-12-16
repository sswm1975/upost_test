@component('mail::message')

<b>Добрый день, {{ $fullname }}.</b>

Вы успешно зарегистрировались на сервисе UPOST с помощью {{ ucfirst($provider) }} аккаунта {{ $email }}. Рады приветствовать Вас!

Нами был создан случайный пароль <b>{{$password }}</b> для вашего профиля, если Вы захотите авторизироваться на нашем сайте без помощи социальных сетей.

Советуем вам перейти <a href="{{ $url }}" target="_blank">по ссылке</a> для изменения пароля на более надежный, известный только Вам.

@component('mail::button', ['url' => $url, 'color' => 'green'])
    Изменить пароль
@endcomponent

<hr><br>

<i>Всего лучшего, с уважением, команда {{ config('app.name') }}.</i>

@endcomponent
