@component('mail::message')
# Attention

<hr>

There are too many complaints about your order. As a result, we banned him.

## List of <complaints></complaints>
- Once
- Two
- Three

@component('mail::button', ['url' => $url])
View order
@endcomponent

<hr><br>

<i>All the best, respectfully,<br>the team {{ config('app.name') }}.</i>
@endcomponent
