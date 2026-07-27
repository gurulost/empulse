<b>New message from customer:</b> <br /><br />
<div class="well col-sm-8">
        Name: {{ $name }} <br />
        Email: {{ $email }} <br />
        Phone: {{ $phone ?: 'Not provided' }} <br /><br />
        Message:<br />
        {!! nl2br(e($customerMessage)) !!}
</div>


