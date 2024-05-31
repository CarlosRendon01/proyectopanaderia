<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::paginate(6);
        return view('productos.index', compact('productos'));
    }

    public function create()
    {
        $materiasPrimas = Materia::all();
        return view('productos.crear', compact('materiasPrimas'));
    }

    public function store(Request $request)
    {
        // Validación
        $request->validate([
            'nombre' => 'required',
            'descripcion' => 'required',
            'precio' => 'required|numeric|between:0,9999.99',
            'cantidad' => 'required|integer',
            'materias_primas' => 'required|array',
            'materias_primas.*' => 'exists:materias,id',
            'cantidades' => 'required|array|size:' . count($request->materias_primas),
            'cantidades.*' => 'numeric|min:1',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Manejo de la imagen
        $imagenUrl = null;
        if ($request->hasFile('imagen')) {
            $imagen = $request->file('imagen');
            $imagenUrl = $imagen->store('imagenes_productos', 'public');
        }

        // Obtener materias primas y cantidades
        $materiasPrimas = $request->input('materias_primas', []);
        $cantidades = $request->input('cantidades', []);
        $cantidadProducto = $request->cantidad;

        // Verificar si hay suficiente materia prima (dentro de una transacción)
        DB::beginTransaction();

        try {
            foreach ($materiasPrimas as $index => $materiaPrimaId) {
                $materiaPrima = Materia::find($materiaPrimaId);
                $cantidadNecesaria = $cantidades[$index] * $cantidadProducto;

                if ($materiaPrima->cantidad < $cantidadNecesaria) {
                    DB::rollback();
                    return redirect()->back()->withErrors([
                        'cantidad' => 'No hay suficiente materia prima "' . $materiaPrima->nombre . '" para crear este producto.'
                    ])->withInput();
                }
            }

            // Crear el producto
            $producto = Producto::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio' => $request->precio,
                'cantidad' => $cantidadProducto,
                'imagen_url' => $imagenUrl,
            ]);

            // Adjuntar materias primas y descontar cantidades
            foreach ($materiasPrimas as $index => $materiaPrimaId) {
                $materiaPrima = Materia::find($materiaPrimaId);
                $cantidadNecesaria = $cantidades[$index] * $cantidadProducto;

                $producto->materias()->attach([
                    $materiaPrimaId => ['cantidad' => $cantidades[$index]]
                ]);

                $materiaPrima->cantidad -= $cantidadNecesaria;
                $materiaPrima->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Ocurrió un error al crear el producto.'])->withInput();
        }

        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente.');
    }

    public function show($id)
    {
        $producto = Producto::findOrFail($id);
        return view('productos.show', compact('producto'));
    }

    public function edit($id)
    {
        $producto = Producto::findOrFail($id);
        $materias = Materia::all(); 
        return view('productos.editar', compact('producto', 'materias'));
    }

    public function update(Request $request, $id)
{
    $producto = Producto::findOrFail($id);

    $request->validate([
        'nombre' => 'required',
        'descripcion' => 'required',
        'precio' => 'required|numeric|between:0,9999.99',
        'cantidad' => 'required|integer|min:1',
        'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'materias_primas' => 'required|array',
        'materias_primas.*' => 'exists:materias,id',
        'cantidades' => 'required|array|size:' . count($request->materias_primas),
        'cantidades.*' => 'numeric|min:1',
    ]);

    DB::beginTransaction();
    try {
        $this->handleMateriasPrimas($request, $producto);
        $this->handleImageUpload($request, $producto);

        $producto->update($request->only(['nombre', 'descripcion', 'precio', 'cantidad']));

        DB::commit();
        return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente.');
    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Error al actualizar el producto: ' . $e->getMessage());
        return back()->withErrors(['error' => 'Error al actualizar el producto.'])->withInput();
    }
}

private function handleImageUpload(Request $request, Producto $producto)
{
    if ($request->hasFile('imagen')) {
        $imagenUrl = $request->file('imagen')->store('imagenes_productos', 'public');
        $producto->imagen_url = $imagenUrl;
    }
}

private function handleMateriasPrimas(Request $request, Producto $producto)
{
    $materiasPrimas = $request->input('materias_primas');
    $cantidades = $request->input('cantidades');

    $producto->materias()->detach();
    foreach ($materiasPrimas as $index => $materiaPrimaId) {
        $materiaPrima = Materia::findOrFail($materiaPrimaId);
        $cantidadNecesaria = $cantidades[$index];

        if ($materiaPrima->cantidad < $cantidadNecesaria) {
            throw new \Exception("Insufficient quantity for " . $materiaPrima->nombre);
        }

        $producto->materias()->attach($materiaPrimaId, ['cantidad' => $cantidadNecesaria]);
        $materiaPrima->decrement('cantidad', $cantidadNecesaria);
    }
}




    public function destroy($id)
    {
        try {
            $producto = Producto::findOrFail($id);

            // Eliminar la imagen si existe
            if ($producto->imagen_url) {
                Storage::disk('public')->delete($producto->imagen_url);
            }

            $producto->delete();
            return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error eliminando producto: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error eliminando producto: ' . $e->getMessage()]);
        }
    }
}
