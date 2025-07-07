@extends('layouts.app')

@section('title', 'Archives for ' . $handle)

@section('content')
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <x-profile-main-content :user="$user" :profile="$profile" :handle="$handle"/>

    <div class="mt-8">
        <h1 class="text-2xl font-bold">{{ "@". $handle }} のアーカイブ一覧</h1>

        <div class="lg:flex lg:space-x-8">
            <x-archives-list-section :archives="$archives"/>
            <x-profile-sidebar :user="$user" :handle="$handle" :archives="$archives" :top_replies="$top_replies" :top_hashtags="$top_hashtags"/>
        </div>
    </div>
@endsection
