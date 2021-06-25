@component('mail::message')
# Внимание

<hr>

По Вашему заказу слишком много жалоб. В связи с этим мы его забанили.

## Список жалоб
- Раз
- Два
- Три

@component('mail::button', ['url' => $url])
Посмотреть заказ
@endcomponent

<hr><br>

_С уважением,<br>
команда {{ config('app.name') }}._
@endcomponent
