@extends('layouts.app')

@section('title', 'Bluelog - Blueskyの投稿を保存・管理')

@section('content')
    <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
        <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- サービス紹介セクション -->
            <section class="bg-gray-50 dark:bg-gray-800 shadow-md rounded-lg p-8 mb-8 text-center">
                <h1 class="text-4xl font-extrabold text-blue-700 dark:text-blue-300 mb-4">Bluelogへようこそ</h1>
                <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                    Bluelogは、あなたのBlueskyでの活動を記録し、いつでも振り返ることができるサービスです。<br>
                    大切なポストや「いいね」を自動で保存し、時系列で管理できます。
                </p>
                <div class="mt-6 flex justify-center space-x-4">
                    <a href="{{ route('login') }}"
                       class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-full text-lg transition duration-300">
                        新規登録・ログイン</a>
                </div>
            </section>

            <!-- 何ができるかセクション -->
            <section class="bg-gray-50 dark:bg-gray-800 shadow-md rounded-lg p-8 mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">Bluelogでできること</h2>
                <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-2">
                    <li>Blueskyの投稿（ポスト）を自動で保存し、後から検索・閲覧できます。</li>
                    <li>「いいね」した投稿も記録され、お気に入りのコンテンツを簡単に見返せます。</li>
                    <li>日々の活動統計をグラフで確認し、可視化できます。</li>
                    <li>特定のキーワードやハッシュタグで過去の投稿を検索できます。</li>
                    <li>プライベート設定で、保存したデータを自分だけが閲覧できるように設定できます。</li>
                </ul>
                <div class="mt-6 flex justify-center space-x-4">
                    <a href="{{ route('faq') }}"
                       class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-full text-lg transition duration-300">よくある質問 (FAQ)</a>
                </div>
            </section>

            <!-- お知らせセクション -->
            <section class="bg-gray-50 dark:bg-gray-800 shadow-md rounded-lg p-8 mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">お知らせ</h2>
                <ul class="text-gray-700 dark:text-gray-300 space-y-2">
                    <li><span class="font-semibold text-blue-500">2025/07/08:</span> Bluelogサービスを開始しました！</li>
                    <li><span class="font-semibold text-blue-500">2025/07/01:</span> ベータテストにご協力いただきありがとうございました。
                    </li>
                    <!-- 今後のお知らせをここに追加 -->
                </ul>
            </section>

            <section class="bg-gray-50 dark:bg-gray-800 shadow-md rounded-lg p-6 mt-8">
                <h2 class="text-2xl text-center font-bold text-gray-800 dark:text-gray-200 mb-4">利用中のユーザー</h2>
                <div class="flex flex-wrap gap-4 justify-center">
                    @forelse($recent_public_users as $user)
                        <a
                            href="{{ route('profile.show', ['handle' => $user->handle]) }}"
                            class="flex flex-col items-center outline-none no-underline">
                        <img
                                src="{{ $user->avatar_url }}"
                                alt="{{ $user->handle }}"
                                title="{{ '@' . $user->handle }}"
                                class="w-16 h-16 rounded-full object-cover border-2 border-blue-400">
                        </a>
                    @empty
                        <p class="text-gray-600 dark:text-gray-400">現在、公開ユーザーはいません。</p>
                    @endforelse
                </div>
            </section>
        </main>
    </div>
@endsection
