@extends('layouts.app')

@section('title', 'Bluelog - よくある質問 (FAQ)')

@section('content')
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200 mb-6 text-center">よくある質問 (FAQ)</h1>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Bluelogについて</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium text-blue-600 dark:text-blue-400">Q1: Bluelogとは何ですか？</h3>
                    <p class="text-gray-700 dark:text-gray-300 mt-1">
                        A1: Bluelogは、Blueskyの投稿や「いいね」を自動で収集し、時系列で管理・閲覧できるサービスです。Twilogのように、あなたのBlueskyでの活動履歴を簡単に振り返ることができます。
                    </p>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-blue-600 dark:text-blue-400">Q2: どのようなデータが保存されますか？</h3>
                    <p class="text-gray-700 dark:text-gray-300 mt-1">
                        A2: あなたのBlueskyの投稿（ポスト）、いいね、リプライ、リポストなどのデータが保存されます。また、日ごとの活動統計も集計されます。
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">アカウントとプライバシー</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium text-blue-600 dark:text-blue-400">Q3: ログインにはBlueskyのパスワードが必要ですか？</h3>
                    <p class="text-gray-700 dark:text-gray-300 mt-1">
                        A3: Blueskyのメインパスワードではなく、<strong class="font-semibold">アプリパスワード</strong>のご利用を強く推奨します。アプリパスワードは、Blueskyの設定から発行できます。
                    </p>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-blue-600 dark:text-blue-400">Q4: データは公開されますか？</h3>
                    <p class="text-gray-700 dark:text-gray-300 mt-1">
                        A4: デフォルトでは公開されますが、設定ページで「非公開」に設定することができます。非公開設定にした場合、あなたのデータはあなた自身にしか表示されません。
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">機能と利用方法</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium text-blue-600 dark:text-blue-400">Q5: 過去の投稿を検索できますか？</h3>
                    <p class="text-gray-700 dark:text-gray-300 mt-1">
                        A5: はい、キーワードやハッシュタグで過去の投稿を検索できます。
                    </p>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-blue-600 dark:text-blue-400">Q6: 自分のデータをエクスポートできますか？</h3>
                    <p class="text-gray-700 dark:text-gray-300 mt-1">
                        A6: はい、設定ページからあなたの投稿データをCSV形式でエクスポートすることができます。
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
