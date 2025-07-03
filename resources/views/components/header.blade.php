<header class="bg-blue-600 text-white p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
        <a href="/" class="text-2xl font-bold">Bluelog</a>
        <nav>
            <ul class="flex space-x-4">
                @auth
                    <li><a href="{{ route('profile.show', ['handle' => Auth::user()->handle]) }}" class="hover:underline">マイプロフィール</a></li>
                    <li><a href="{{ route('settings.edit') }}" class="hover:underline">設定</a></li>
                @endauth
                @guest
                    <li><a href="{{ route('login') }}" class="hover:underline">ログイン</a></li>
                @else
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="hover:underline">ログアウト</button>
                        </form>
                    </li>
                @endguest
            </ul>
        </nav>
    </div>
</header>