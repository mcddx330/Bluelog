@extends('layouts.app')

@section('title', 'Bluesky Profile')

@section('content')
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <nav class="text-sm text-gray-400 mb-4">
        <ol class="list-none p-0 inline-flex">
            <li class="flex items-center">
                <a href="{{ route('index') }}" class=" hover:underline">
                    <i class="fas fa-home"></i>
                </a>
                <svg class="fill-current w-3 h-4 mx-2"
                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                    <path
                        d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 67.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                </svg>
            </li>
            <li class="flex items-center">
                @if(Route::currentRouteName() === 'profile.show')
                    <span class="text-gray-900">{{ '@' . $profile['handle'] }}</span>
                @else
                    <a href="{{ route('profile.show', ['handle' => $profile['handle']]) }}" class="hover:underline">
                        {{ '@' . $profile['handle'] }}
                    </a>
                @endif
            </li>
        </ol>
    </nav>

    @if(isset($profile))
        <x-is-fetching-message :is_fetching="$is_fetching"/>

        <x-profile-main-content :profile="$profile" :handle="$handle" :daily_stats="$daily_stats" />

        <div class="lg:flex lg:space-x-8 mt-8">
            <x-profile-posts-section :posts="$posts" :handle="$handle" :current_sort="request('sort', 'posted_at_desc')"
                                     :query_params="request()->except(['sort', 'page'])"/>
            <x-profile-sidebar :handle="$handle" :archives="$archives" :top_mentions="$top_mentions" :top_hashtags="$top_hashtags"/>
        </div>
    @else
        <p>No profile data available.</p>
    @endif
@endsection
