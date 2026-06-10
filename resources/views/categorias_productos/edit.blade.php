<x-app-layout>
    <div class="page-header">
        <div>
            <h1>Editar Categoría</h1>
            <p>Actualización de categoría de producto</p>
        </div>
    </div>

    <div class="form-card">
        <form action="{{ route('categorias-productos.update', $categoria) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" required>
                @error('nombre') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" required>{{ old('descripcion', $categoria->descripcion) }}</textarea>
                @error('descripcion') <span class="error">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn-primary">Actualizar</button>
            <a href="{{ route('categorias-productos.index') }}" class="btn-secondary">Cancelar</a>
        </form>
    </div>
</x-app-layout>