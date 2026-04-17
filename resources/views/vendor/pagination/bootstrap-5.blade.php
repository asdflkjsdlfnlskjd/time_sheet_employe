@if ($paginator->hasPages())
    <nav>
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="Предыдущая">
                    <span class="page-link">Предыдущая</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Предыдущая">Предыдущая</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @php
                $currentPage = $paginator->currentPage();
                $lastPage = $paginator->lastPage();
                $showFirst = 5;  // Показываем первые 5 страниц
                $showLast = 2;   // Показываем последние 2 страницы
            @endphp

            @for ($page = 1; $page <= $lastPage; $page++)
                @php
                    $showPage = false;
                    
                    // Показываем первые 5 страниц
                    if ($page <= $showFirst) {
                        $showPage = true;
                    }
                    // Показываем последние 2 страницы
                    elseif ($page > $lastPage - $showLast) {
                        $showPage = true;
                    }
                    // Показываем текущую страницу и соседние
                    elseif (abs($page - $currentPage) <= 1) {
                        $showPage = true;
                    }
                @endphp

                @if ($showPage)
                    @if ($page == $currentPage)
                        <li class="page-item active" aria-current="page">
                            <span class="page-link">
                                {{ $page }}
                                <span class="sr-only">(current)</span>
                            </span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                        </li>
                    @endif
                {{-- Многоточие между диапазонами --}}
                @elseif ($page == $showFirst + 1 && $page < $lastPage - $showLast)
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                @php
                    $page = $lastPage - $showLast - 1; // Перемещаемся к последним страницам
                @endphp
                @endif
            @endfor

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Следующая">Следующая</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="Следующая">
                    <span class="page-link">Следующая</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
