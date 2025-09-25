<b>Hello, {{$name}}!</b>
You are an employee of the {{$company}}.
Your department: {{$department}}.
@if(isset($supervisor))Your supervisor: {{$supervisor}}.@endif
<br />

<p><i>This is where you need to take <a href="https://qualtricsxm29zkvsd7y.qualtrics.com/jfe/form/SV_9FtECtejcxTGgL4" target="_blank">the test</a></i></p><br />

<p><b>*** IMPORTANTLY!</b></p>
For correct identification, enter the following data in the first block of the survey:<br /><br />

the company name: {{$company}}<br />
department: {{$department}}
@if($supervisor !== null)<br />supervisor: {{$supervisor}}@endif
