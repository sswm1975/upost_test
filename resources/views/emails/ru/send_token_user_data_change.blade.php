@component('mail::message')
# Код подтверждения смены данных профиля.

<hr><br><br>

Для подтверждения смены используйте код <b>{{ $token }}</b>.

<br><hr><br>

_С уважением,<br>
команда {{ config('app.name') }}._
@endcomponent
