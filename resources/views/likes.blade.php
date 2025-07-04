@extends('layouts.app')

@section('title', 'Likes for ' . $handle)

@push('scripts')
    <script async src="https://embed.bsky.app/static/embed.js" charset="utf-8"></script>
@endpush

@section('content')
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <x-profile-main-content :profile="$profile" :handle="$handle"/>

    <div class="mt-8">
        <h1 class="text-2xl font-bold">{{ "@". $handle }} のいいね一覧</h1>

        <div class="lg:flex lg:space-x-8">
            <x-likes-posts-section :posts="$posts" :likes_pagination="$likes_pagination"/>
            <x-profile-sidebar :handle="$handle" :archives="$archives" :top_replies="$top_replies" :top_hashtags="$top_hashtags"/>
        </div>
    </div>
@endsection
