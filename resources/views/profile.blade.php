@extends('layouts.app')

@section('title', 'Bluesky Profile')

@section('content')
    @if(isset($profile))
        <x-is-fetching-message :is_fetching="$is_fetching"/>

        <x-profile-main-content :user="$user" :profile="$profile" :handle="$handle" :daily_stats="$daily_stats" />

        <div class="lg:flex lg:space-x-8 mt-8">
            <x-profile-posts-section :posts="$posts" :handle="$handle" :current_sort="request('sort', 'posted_at_desc')"
                                     :query_params="request()->except(['sort', 'page'])"/>
            <x-profile-sidebar :user="$user" :handle="$handle" :archives="$archives" :top_replies="$top_replies" :top_hashtags="$top_hashtags"/>
        </div>
    @else
        <p>No profile data available.</p>
    @endif
@endsection
