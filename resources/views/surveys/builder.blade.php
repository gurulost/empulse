@extends('layouts.app')

@section('title', 'Survey Builder')

@section('content')
    <div
        id="survey-builder-root"
        data-initial-version-id='@json($versionId)'
        data-survey-id='@json($surveyId)'
    >
    </div>
@endsection
