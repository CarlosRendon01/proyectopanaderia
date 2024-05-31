<?php

namespace App\Http\Controllers;

use App\Models\Puntoventa;
use Illuminate\Http\Request;

class PuntoventaController extends Controller
{
    public function index()
    {
        $puntoventas = Puntoventa::paginate(10);
        return view('puntoventas.index', compact('puntoventas'));
    }

    public function create()
    {
        return view('puntoventas.crear');
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_punventa' => 'required|unique:puntoventas',
            'descripcion' => 'required|string|max:255',
        ]);

        Puntoventa::create($request->all());

        return redirect()->route('puntoventas.index')
                         ->with('success', 'Punto de Venta creado exitosamente.');
    }

    public function show(Puntoventa $puntoventa)
    {
        return view('puntoventas.show', compact('puntoventa'));
    }

    public function edit($id_punventa)
    {
        $puntoventa = Puntoventa::findOrFail($id_punventa);
        return view('puntoventas.edit', compact('puntoventa'));
    }

    public function update(Request $request, Puntoventa $puntoventa)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        $puntoventa->update($request->all());

        return redirect()->route('puntoventas.index')
                         ->with('success', 'Punto de Venta actualizado exitosamente.');
    }

    public function destroy(Puntoventa $puntoventa)
    {
        $puntoventa->delete();

        return redirect()->route('puntoventas.index')
                         ->with('success', 'Punto de Venta eliminado exitosamente.');
    }
}
