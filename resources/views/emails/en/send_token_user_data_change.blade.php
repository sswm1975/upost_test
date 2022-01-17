@component('mail::message')
# Profile data change confirmation code.

<hr><br><br>

Use the code to confirm the change <b>{{ $token }}</b>.

<br><hr><br>

<i>All the best, respectfully,<br>the team {{ config('app.name') }}.</i>
@endcomponent
