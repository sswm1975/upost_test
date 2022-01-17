@component('mail::message')
# Код підтвердження зміни даних профілю.

<hr><br><br>

Для підтвердження зміни використовуйте код <b>{{ $token }}</b>.

<br><hr><br>

<i>Всього найкращого,<br>з повагою, команда {{ config('app.name') }}.</i>
@endcomponent
