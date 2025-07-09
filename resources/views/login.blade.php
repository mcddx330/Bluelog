@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-center text-gray-900">Bluelogへログイン</h2>
            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="identifier" class="block text-sm font-medium text-gray-700">Blueskyユーザー名またはメールアドレス</label>
                    <input type="text" name="identifier" id="identifier" required
                           class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus::ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">パスワード</label>
                    <input type="password" name="password" id="password" required
                           class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="invitation_code" class="block text-sm font-medium text-gray-700">招待コード (任意)</label>
                    <input type="text" name="invitation_code" id="invitation_code"
                           class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           maxlength="16">
                </div>
                <button type="submit"
                        class="w-full px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    ログイン
                </button>
            </form>

            <div class="p-4 mt-6 text-sm text-yellow-800 bg-yellow-100 border border-yellow-400 rounded-md" role="alert">
                <p class="font-bold">Blueskyログインに関する重要なお知らせ</p>
                <p class="mt-2">Bluelogへのログインには、Blueskyのメインパスワードではなく、<strong
                        class="font-semibold">アプリパスワード</strong>のご利用を強く推奨します。アプリパスワードは、特定のアプリケーションにのみアクセスを許可する使い捨てのパスワードであり、万が一漏洩した場合でもメインアカウントのセキュリティリスクを最小限に抑えることができます。
                </p>
                <p class="mt-2">アプリパスワードの発行方法や、その安全性に関する詳細は、
                    <a href="#"
                       class="font-medium text-yellow-900 underline hover:text-yellow-700"
                    >よくある質問（FAQ）ページ</a>
                    をご確認ください。
                </p>
            </div>
        </div>
    </div>
@endsection
