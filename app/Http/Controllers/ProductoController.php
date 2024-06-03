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

    public function showChargeForm()
    {
        $productos = Producto::paginate(6);
        return view('productos.cargar', compact('productos'));
    }

    public function charge(Request $request)
    {
        $cantidades = $request->input('cantidades', []);
    DB::beginTransaction();
    try {
        foreach ($cantidades as $productoId => $cantidad) {
            if ($cantidad <= 0) continue; // Ignorar valores no positivos

            $producto = Producto::with('materias')->findOrFail($productoId); // Asegurar que el producto existe y cargar sus materias primas
            
            // Revisar si hay suficientes materias primas para la cantidad del producto a cargar
            foreach ($producto->materias as $materia) {
                $cantidadNecesaria = $materia->pivot->cantidad * $cantidad;
                if ($materia->cantidad < $cantidadNecesaria) {
                    throw new \Exception("No hay suficiente {$materia->nombre} para cargar {$cantidad} unidades de {$producto->nombre}");
                }
            }

            // Reducir las materias primas necesarias
            foreach ($producto->materias as $materia) {
                $cantidadNecesaria = $materia->pivot->cantidad * $cantidad;
                $materia->decrement('cantidad', $cantidadNecesaria);
            }

            // Aumentar el stock del producto
            $producto->increment('cantidad', $cantidad);
        }
        DB::commit();
        return redirect()->route('productos.index')->with('success', 'Cantidades de productos actualizadas correctamente.');
    } catch (\Exception $e) {
        DB::rollback();
        return back()->withErrors(['error' => $e->getMessage()])->withInput();
    }
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
        'nombre' => 'required|string|max:255',
        'descripcion' => 'required|string',
        'precio' => 'required|numeric|between:0,9999.99',
        'cantidad' => 'required|integer|min:1',
        'materias_primas' => 'required|array|min:1',
        'materias_primas.*' => 'exists:materias,id',
        'cantidades' => 'required|array|size:' . count($request->input('materias_primas', [])),
        'cantidades.*' => 'numeric|min:1',
        'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ], [
        'materias_primas.required' => 'Debe seleccionar al menos una materia prima.',
        'materias_primas.*.exists' => 'La materia prima seleccionada no existe.',
        'cantidades.*.min' => 'La cantidad debe ser al menos 1.',
        'cantidades.required' => 'Debe ingresar una cantidad para cada materia prima.',
        'cantidades.size' => 'La cantidad de elementos en cantidades debe coincidir con el número de materias primas.',
    ]);

    // Manejo de la imagen
    $imagenUrl = null;
    if ($request->hasFile('imagen')) {
        $imagen = $request->file('imagen');
        $imagenUrl = $imagen->store('imagenes_productos', 'public');
    }

    DB::beginTransaction();

    try {
        // Crear el producto
        $producto = Producto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'precio' => $request->precio,
            'cantidad' => $request->input('cantidad'),
            'imagen_url' => $imagenUrl,
        ]);

        // Adjuntar materias primas y validar cantidades
        foreach ($request->input('materias_primas', []) as $index => $materiaPrimaId) {
            $cantidadNecesaria = $request->cantidades[$index];
            $materiaPrima = Materia::findOrFail($materiaPrimaId);
            $producto->materias()->attach($materiaPrimaId, ['cantidad' => $cantidadNecesaria]);

            // Reducir el stock de la materia prima
            $materiaPrima->decrement('cantidad', $cantidadNecesaria * $request->cantidad);
        }

        DB::commit();
        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente.');
    } catch (\Exception $e) {
        DB::rollback();
        return back()->withErrors(['error' => 'Ocurrió un error al crear el producto: ' . $e->getMessage()])->withInput();
    }
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
        'nombre' => 'required|string|max:255',
        'descripcion' => 'required|string',
        'precio' => 'required|numeric|between:0,9999.99',
        'cantidad' => 'required|integer|min:1',
        'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'materias_primas' => 'required|array|min:1',
        'materias_primas.*' => 'exists:materias,id',
        'cantidades' => 'required|array|size:' . count($request->input('materias_primas', [])),
        'cantidades.*' => 'numeric|min:1',
    ], [
        'materias_primas.required' => 'Debe seleccionar al menos una materia prima.',
        'cantidades.required' => 'Debe ingresar una cantidad para cada materia prima seleccionada.',
        'cantidades.size' => 'La cantidad de elementos en cantidades debe coincidir con el número de materias primas seleccionadas.',
    ]);

    DB::beginTransaction();
    try {
        if ($request->hasFile('imagen') && $request->file('imagen')->isValid()) {
            if ($producto->imagen_url) {
                Storage::disk('public')->delete($producto->imagen_url);
            }
            $producto->imagen_url = $request->file('imagen')->store('imagenes_productos', 'public');
        }

        $producto->update($request->only(['nombre', 'descripcion', 'precio', 'cantidad']));

        $producto->materias()->sync([]);
        foreach ($request->input('materias_primas', []) as $index => $materiaPrimaId) {
            $cantidad = $request->cantidades[$index];
            $producto->materias()->attach($materiaPrimaId, ['cantidad' => $cantidad]);
        }

        DB::commit();
        return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente.');
    } catch (\Exception $e) {
        DB::rollback();
        return back()->withErrors(['error' => 'Error al actualizar el producto: ' . $e->getMessage()])->withInput();
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
