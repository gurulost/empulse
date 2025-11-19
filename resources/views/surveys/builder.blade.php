@extends('layouts.app')

@section('title', 'Survey Builder')

@section('content')
    <div id="survey-builder-root" 
         data-initial-version-id="{{ $versionId }}"
         data-survey-id="{{ $surveyId }}">
    </div>
@endsection
