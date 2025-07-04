@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-center">
        <div class="inline-flex overflow-hidden rounded-md border border-gray-300 divide-x divide-gray-300 shadow-sm">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span
                    aria-disabled="true"
                    aria-label="{{ __('pagination.previous') }}"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white cursor-default first:rounded-l-md leading-5">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1
                                 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                              clip-rule="evenodd" />
                    </svg>
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    aria-label="{{ __('pagination.previous') }}"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring ring-gray-300 active:bg-gray-200 first:rounded-l-md leading-5 transition ease-in-out duration-150">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1
                                 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                              clip-rule="evenodd" />
                    </svg>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span
                        aria-disabled="true"
                        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white cursor-default leading-5">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span
                                aria-current="page"
                                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-gray-200 cursor-default leading-5">
                                {{ $page }}
                            </span>
                        @else
                            <a
                                href="{{ $url }}"
                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring ring-gray-300 active:bg-gray-200 leading-5 transition ease-in-out duration-150">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    aria-label="{{ __('pagination.next') }}"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring ring-gray-300 active:bg-gray-200 last:rounded-r-md leading-5 transition ease-in-out duration-150">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M7.293 14.707a1 1 0 010-1.414L10.586 10l-3.293-3.293a1 1
                                 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                              clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span
                    aria-disabled="true"
                    aria-label="{{ __('pagination.next') }}"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white cursor-default last:rounded-r-md leading-5">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M7.293 14.707a1 1 0 010-1.414L10.586 10l-3.293-3.293a1 1
                                 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                              clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
