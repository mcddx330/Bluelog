@extends('layouts.app')

@section('title', 'Bluesky Profile')

@section('content')
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">エラー:</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if(!($user instanceof \App\Models\User))
        <div class="flex items-center justify-center h-96">
            <div class="bg-white p-8 rounded-lg shadow-md text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">アカウントが登録されていません</h2>
                <p class="text-gray-600">Blueskyアカウントを登録して、あなたの活動履歴をBluelogで管理しましょう。</p>
                <a href="{{ route('login') }}" class="mt-6 inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    アカウントを登録する
                </a>
            </div>
        </div>
    @elseif(!$user->canShow())
        <div class="flex items-center justify-center h-96">
            <div class="bg-white p-8 rounded-lg shadow-md text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">このプロフィールは非公開です。</h2>
                <p class="text-gray-600">プロフィールが非公開設定のため、表示できません。</p>
            </div>
        </div>
    @else
        <x-is-fetching-message :is_fetching="$is_fetching"/>

        <x-profile-main-content :user="$user" :profile="$profile" :handle="$handle" :daily_stats="$daily_stats" />

        <div class="lg:flex lg:space-x-8 mt-8">
            <x-profile-posts-section :posts="$posts" :handle="$handle" :current_sort="request('sort', 'posted_at_desc')"
                                     :query_params="request()->except(['sort', 'page'])"/>
            <x-profile-sidebar :user="$user" :handle="$handle" :archives="$archives" :top_replies="$top_replies" :top_hashtags="$top_hashtags"/>
        </div>
    @endif
@endsection
