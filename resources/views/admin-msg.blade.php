<style>
    a {
        color: red !important;
        text-decoration: none;
    }

    span, i, .importantly > h3 {
        color: red !important;
    }

    p {
        padding: 0;
        margin: 0;
    }

    .lp-block > p {
        font-weight: bold;
        color: black;
    }

    .test-info-block {
        margin-top: 10px;
    }

    .greetings {
        text-align: center;
        margin-bottom: 15px;
    }

    .greetings > h1 {
        margin: 0;
        padding: 0;
        color: navy;
    }

    .greetings > p {
        font-weight: bold;
    }

    .lp-block {
        margin-top: 5px;
    }

    .href {
        color: green !important;
        text-decoration: underline !important;
    }

    .importantly {
        margin-bottom: 5px;
    }

    .importantly > div {
        margin-bottom: 10px;
    }
</style>

@php
    $setupLink = $setupLink ?? null;
@endphp

<div class="greetings">
    <h1>Hello, {{$name}}!</h1>
    <p>You are a {{$status}} of the {{$company}} company!</p>
</div>

<article>
    @if($setupLink)
        <p>You have been invited to join Empulse as a {{ $status }}.</p>
        <p>This invitation is single-use and expires. Empulse will never email you a temporary password.</p>
        <p>
            <a href="{{ $setupLink }}" class="href" target="_blank" rel="noreferrer">
                Set up your account securely
            </a>
        </p>
    @else
        <p>Your Empulse account details were updated.</p>
        <p><a href="{{ route('login') }}" class="href" target="_blank">Sign in to review your account.</a></p>
    @endif

    <div class="importantly">
        <h3 style="color: red;">Account details</h3>
        <div>
            <p>company: <span>{{$company}}</span></p>
            <p>email: <span>{{$email}}</span></p>
            @if($department !== null && strlen($department) > 0) <p>department: <span>{{$department}}</span></p> @endif
            @if($teamlead !== null && strlen($teamlead) > 0) <p>teamlead: <span>{{$teamlead}}</span></p> @endif
        </div>
    </div>

    <h4>Have a nice day!</h4>
</article>
