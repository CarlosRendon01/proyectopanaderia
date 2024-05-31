@extends('layouts.app')

@section('content')
<section class="section" style="background-image: url('ruta/a/tu/imagen-de-fondo.jpg'); background-size: cover; min-height: 100vh; display: flex; align-items: center;">
    <div class="container custom-container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header d-flex align-items-center justify-content-between bg-primary text-white">
                        <a href="{{ url()->previous() }}" class="btn btn-pink text-white">
                            <i class="fas fa-arrow-left mr-2"></i> Regresar
                        </a>
                        <h3 class="page__heading text-center flex-grow-1 m-0">
                            <i class="fas fa-book mr-2"></i> Editar Producto
                        </h3>
                    </div>
                    <div class="card-body p-4 bg-white">
                        @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>¡Revise los campos!</strong>
                            @foreach ($errors->all() as $error)
                            <span class="badge badge-danger">{{ $error }}</span>
                            @endforeach
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif

                        <form action="{{ route('productos.update', $producto->id_producto) }}" method="POST" class="my-4">
                            @csrf
                            @method('PUT')
                            <div class="row">    
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label for="nombre" class="form-label">Nombre</label>
                                        <input type="text" name="nombre" class="form-control" value="{{ $producto->nombre }}">
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label for="descripcion" class="form-label">Descripción</label>
                                        <textarea name="descripcion" class="form-control">{{ $producto->descripcion }}</textarea>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label for="precio" class="form-label">Precio</label>
                                        <input type="number" name="precio" class="form-control" value="{{ $producto->precio }}">
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    <div class="form-group">
                                        <label for="cantidad" class="form-label">Cantidad</label>
                                        <input type="number" name="cantidad" class="form-control" value="{{ $producto->cantidad }}">
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                                    <button type="submit" class="btn btn-pink btn-block btn-submit">Guardar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Agrega validaciones o funcionalidades de JavaScript aquí si es necesario
    });
</script>
@endsection

@section('styles')
<style>
    .bg-primary {
        background-color: #D8AE7E;
    }

    .form-label {
        font-weight: bold;
        color: #4b479c;
        margin-bottom: 5px;
        font-size: 16px;
    }

    .form-control {
        padding: 12px 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        width: 100%;
        box-sizing: border-box;
        transition: all 0.2s ease;
        font-size: 16px;
        background-color: #f9f9f9;
    }

    .form-control:focus {
        border-color: #4b479c;
        box-shadow: 0 0 8px rgba(75, 71, 156, 0.3);
        background-color: #fff;
    }

    .btn-submit {
        background-color: #4b479c;
        color: #fff;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 18px;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-submit:hover, .btn-submit:focus {
        background-color: #3a2c70;
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
    }

    .section {
        padding: 60px 0;
        background-image: url('ruta/a/tu/imagen-de-fondo.jpg');
        background-size: cover;
        min-height: 100vh;
        display: flex;
        align-items: center;
    }
</style>
@endsection
