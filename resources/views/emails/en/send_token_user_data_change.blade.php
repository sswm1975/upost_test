@component('mail::message')
<b>Good day</b>

You have received this letter because you have received a request to change your profile data.

@component('mail::button', ['url' => $url, 'color' => 'green'])
    Confirm change
@endcomponent

The link to confirm the change of profile data will expire in 60 minutes.

If you did not request a change of profile data, then no further action is required.

If you're having trouble clicking the "Confirm change" button, copy and paste the URL below into your browser: <a href="{{ $url }}" target="_blank">{{ $url }}</a>

<br><hr><br>

<i>C уважением,<br>
    команда {{ config('app.name') }}.</i>
@endcomponent
