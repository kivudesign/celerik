@extends('celerik.layout')

@section('title', 'Rich text and uploads')

@section('content')
    @if ($article)
        <div class="alert alert-success" role="status">
            <p class="mb-2">Published “{{ $article['title'] }}” with cover {{ $article['cover'] }}.</p>
            @foreach ($article['attachments'] as $name)
                <p class="mb-1 small">Attachment: {{ $name }}</p>
            @endforeach
            {{-- Sanitized by EditorHtml::sanitize(), so it is safe to output unescaped. --}}
            <div class="border rounded p-2 bg-body">{!! $article['body'] !!}</div>
        </div>
    @endif

    <x-celerik :component="$form" />
@endsection
