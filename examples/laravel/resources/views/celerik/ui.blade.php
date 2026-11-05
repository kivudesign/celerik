@extends('celerik.layout')

@section('title', 'Tabs, modal and notifications')

@section('content')
    <x-celerik :component="$tabs" />

    <p class="mt-3">{!! $modal->trigger('Subscribe to the newsletter') !!}</p>
    <x-celerik :component="$modal" />

    <x-celerik :component="$notification" />
@endsection
