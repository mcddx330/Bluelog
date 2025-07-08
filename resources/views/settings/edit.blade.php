@extends('layouts.app')

@section('title', '設定')

@section('content')
    <x-is-fetching-message :is_fetching="$is_fetching"/>

    <div class="lg:flex lg:space-x-8 mt-8">
        <x-settings-main-content :user="$user" :profile="$profile" :handle="$handle"/>
        <x-profile-sidebar :user="$user" :handle="$handle" :archives="$archives" :top_replies="$top_replies" :top_hashtags="$top_hashtags"/>
    </div>
@endsection
