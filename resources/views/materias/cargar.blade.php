@extends('layouts.app')

@section('content')
<section class="section" style="background-color: #e0e0eb; min-height: 100vh; display: flex; align-items: center;">
    <div class="container custom-container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-primary text-white">
                        <h3 class="page__heading text-center">Cargar Materias</h3>
                    </div>
                    <div class="card-body p-4 bg-white">
                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <form action="{{ route('materias.charge') }}" method="POST">
                            @csrf
                            @foreach($materias as $materia)
                            <div class="form-group">
                                <label for="cantidad_{{ $materia->id }}">{{ $materia->nombre }}</label>
                                <input type="number" name="cantidades[{{ $materia->id }}]" class="form-control" min="0" value="0">
                            </div>
                            @endforeach
                            <button type="submit" class="btn btn-primary">Actualizar Cantidades</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
