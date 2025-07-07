@extends('layouts.app')

@section('title', 'Bluesky Friends Ranking')

@section('content')
    <x-profile-main-content :user="$user" :profile="$profile" :handle="$handle"/>

    <div class="mt-8">
        <h1 class="text-2xl font-bold">{{ "@". $handle }} のリプライ宛先一覧</h1>

        <div class="lg:flex lg:space-x-8 mt-8">
            <x-replies-list-section :replies="$replies" :sort_by="$sort_by" :order="$order" :handle="$handle" />
            <x-profile-sidebar :user="$user" :handle="$handle" :archives="$archives" :top_replies="$top_replies" :top_hashtags="$top_hashtags"/>
        </div>
    </div>

@endsection
