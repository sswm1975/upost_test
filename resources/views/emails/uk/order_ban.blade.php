@component('mail::message')
# Увага

<hr>

На Ваше замовлення занадто багато скарг. У зв'язку із цим ми його забанили.

## Список скарг
- Раз
- Два
- Три

@component('mail::button', ['url' => $url])
Переглянути замовлення
@endcomponent

<hr><br>

_З повагою,<br>
команда {{ config('app.name') }}._
@endcomponent
