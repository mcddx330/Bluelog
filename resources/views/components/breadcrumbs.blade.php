@props(['breadcrumbs'])

<nav class="text-sm text-gray-400 mb-4">
    <ol class="list-none p-0 inline-flex">
        <li class="flex items-center">
            <a href="{{ route('index') }}" class="hover:underline">
                <i class="fas fa-home"></i>
            </a>
            <svg class="fill-current w-3 h-4 mx-2"
                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                <path
                    d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 67.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
            </svg>
        </li>
        @foreach ($breadcrumbs as $bread)
            <li class="flex items-center">
                @if(!isset($bread['link']))
                    <span class="text-gray-900">{{ $bread['label'] }}</span>
                @else
                    <a href="{{ $bread['link'] }}" class="hover:underline text-gray-400">
                        {{ $bread['label'] }}
                    </a>
                @endif

                @if (!$loop->last)
                    <svg class="fill-current w-3 h-4 mx-2"
                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                        <path
                            d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 67.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"/>
                    </svg>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
