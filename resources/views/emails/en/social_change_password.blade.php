@component('mail::message')
<b>Good day, {{ $client_name }}.</b>

You have successfully signed up for UPOST with your {{ ucfirst($provider) }} account {{ $email }}. Glad to welcome you!

We've generated a random password <b>{{ $password }}</b> for your account if you want to log in to our site without social media.

We encourage you to follow the <a href="{{ $url }}" target="_blank">link</a> to change your password to a more secure one known only to you.

@component('mail::button', ['url' => $url, 'color' => 'green'])
    Change password
@endcomponent

<hr><br>

<i>All the best, respectfully,<br>the team {{ config('app.name') }}.</i>
@endcomponent
