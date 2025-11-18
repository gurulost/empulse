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
    $surveyLink = $surveyLink ?? (env('TEST_URL') ?? '#');
@endphp

<div class="greetings">
    <h1>Hello, {{$name}}!</h1>
    <p>You are a {{$status}} of the {{$company}} company!</p>
</div>

<article>
    @if($status === 'employee')
        You have to pass test in order to we can to improve our relationship due to your test results. <br />
        <p><i>** <a href="{{ $surveyLink }}" target="_blank" rel="noreferrer">THE TEST PAGE</a> **</i></p><br /><br />
    @else
        <div class="credent-block">
            Now, you can get more control above your employees as a {{$status}} via our web-site! <br />

            <div class="lp-block">
                <p>Your personal login: <span>{{$email}}</span></p>
                @if($password !== null) <p>Your personal password: <span>{{$password}}</span></p> @endif
                <a href="https://empulse.workfitdx.com/login" class='href' target="_blank">You can authorize yourself here!</a>
            </div>

            @if($status !== 'company manager')
                <div class="test-info-block">
                    <p>Also, you have to pass test in order to we can to improve our relationship due to your test results.<p>
                    <p><i>** <a class='href' href="{{ $surveyLink }}" target="_blank" rel="noreferrer">THE TEST PAGE</a> **</i></p><br /><br />
                </div>
            @endif
        </div>
    @endif

    <div class="importantly">
        <h3 style="color: red;">*** IMPORTANTLY!</h3>
        <mark>For correct identification, enter the following data in the first block of the survey:</mark>
        <div>
            <p>company: <span>{{$company}}</span></p>
            <p>email: <span>{{$email}}</span></p>
            @if($department !== null && strlen($department) > 0) <p>department: <span>{{$department}}</span></p> @endif
            @if($teamlead !== null && strlen($teamlead) > 0) <p>teamlead: <span>{{$teamlead}}</span></p> @endif
        </div>
    </div>

    <h4>Have a nice day!</h4>
</article>
