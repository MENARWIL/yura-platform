{{--
    Pie de una lista paginada: cuántos se muestran, selector de 5/10/15/20 por página y páginas.
    Uso: @include('partials.list-footer', ['paginator' => $subjects, 'noun' => 'asignaturas'])
    El selector conserva los demás filtros de la dirección y vuelve a la página 1.
--}}
@if($paginator->total() > 0)
    <div class="list-footer">
        <form method="GET" class="list-size" aria-label="Registros por página">
            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                @if(is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label class="list-count mb-0">
                Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} {{ $noun }}
                <span class="ml-2">·</span>
                <select name="per_page" class="list-size-select" onchange="this.form.submit()" aria-label="Registros por página">
                    @foreach([5, 10, 15, 20] as $size)
                        <option value="{{ $size }}" @selected($paginator->perPage() === $size)>{{ $size }} por página</option>
                    @endforeach
                </select>
            </label>
            <noscript><button type="submit" class="btn btn-sm btn-link">Aplicar</button></noscript>
        </form>
        {{ $paginator->onEachSide(1)->links() }}
    </div>
@endif
