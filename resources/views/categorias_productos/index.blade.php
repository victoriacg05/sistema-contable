<x-app-layout>
    <div class="page-header">
        <div>
            <h1>Categorías de Productos</h1>
            <p>Administración de categorías para el inventario</p>
        </div>

        <a href="{{ route('categorias-productos.create') }}" class="btn-primary">
            Nueva Categoría
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($categorias as $categoria)
                    <tr>
                        <td>{{ $categoria->nombre }}</td>
                        <td>{{ $categoria->descripcion }}</td>
                        <td>
                            <a href="{{ route('categorias-productos.edit', $categoria) }}" class="btn-edit">
                                Editar
                            </a>

                            <form action="{{ route('categorias-productos.destroy', $categoria) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn-delete" onclick="return confirm('¿Deseas eliminar esta categoría?')">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="empty">
                            No hay categorías registradas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>