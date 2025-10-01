@extends('layouts.app')

@section('content')
    @livewire('survey-details', ['uuid' => $uuid])
@endsection
