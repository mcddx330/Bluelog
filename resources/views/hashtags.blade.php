@extends('layouts.app')

@section('title', 'Hashtags for ' . $handle)

@section('content')
    <x-profile-main-content :user="$user" :profile="$profile" :handle="$handle"/>

    <div class="mt-8">
        <h1 class="text-2xl font-bold">{{ "@". $handle }} のハッシュタグ一覧</h1>

        <div class="lg:flex lg:space-x-8">
            <x-hashtags-list-section :hashtags="$hashtags" :handle="$handle"/>
            <x-profile-sidebar :user="$user" :handle="$handle" :archives="$archives" :top_replies="$top_replies" :top_hashtags="$top_hashtags"/>
        </div>
    </div>
@endsection