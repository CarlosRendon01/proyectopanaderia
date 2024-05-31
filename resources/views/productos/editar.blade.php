<!-- resources/views/productos/edit.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="my-4">Editar Producto</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('productos.update', $producto->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" class="form-control" id="nombre" value="{{ $producto->nombre }}" required>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea name="descripcion" class="form-control" id="descripcion" required>{{ $producto->descripcion }}</textarea>
        </div>
        <div class="form-group">
            <label for="precio">Precio</label>
            <input type="number" name="precio" class="form-control" id="precio" step="0.01" value="{{ $producto->precio }}" required>
        </div>
        <div class="form-group">
            <label for="cantidad">Cantidad</label>
            <input type="number" name="cantidad" class="form-control" id="cantidad" value="{{ $producto->cantidad }}" required>
        </div>
        <div class="form-group">
            <label for="imagen">Imagen</label>
            @if ($producto->imagen_url)
                <div>
                    <img src="{{ asset('storage/' . $producto->imagen_url) }}" alt="{{ $producto->nombre }}" class="img-thumbnail" style="max-width: 200px;">
                </div>
            @endif
            <input type="file" name="imagen" class="form-control-file" id="imagen">
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>
@endsection
