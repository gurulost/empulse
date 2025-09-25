<div style="text-align: center;">
    <h1 style="color: navy">Hello, {{$name}}!</h1>

    <p><strong>To reset your password, click on the button below.</strong></p>

    <a href="{{env('APP_URL')}}users/reset/{{$email}}/{{$token}}" target="_blank" style="border: 1px solid orange; border-radius: 10px; padding: 5px; background-color: orange; color: white; text-decoration: none;">Reset</a>
</div>

