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
    $request->validate([
        'nombre' => 'required',
        'descripcion' => 'required',
        'precio' => 'required|numeric|between:0,9999.99',
        'cantidad' => 'required|integer|min:1', // La cantidad debe ser al menos 1
        'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    $producto = Producto::findOrFail($id);
    
    // Validar si la cantidad nueva es menor que la actual
    if ($request->cantidad < $producto->cantidad) {
        return redirect()->back()->withErrors(['error' => 'La nueva cantidad no puede ser menor que la cantidad actual.'])->withInput();
    }

    // Manejo de la imagen
    $imagenUrl = $producto->imagen_url; // Mantén la imagen actual si no se sube una nueva
    if ($request->hasFile('imagen')) {
        $imagenUrl = $request->file('imagen')->store('imagenes_productos', 'public');
    }

    // Preparar datos para actualizar
    $datosParaActualizar = [
        'nombre' => $request->nombre,
        'descripcion' => $request->descripcion,
        'precio' => $request->precio,
        'imagen_url' => $imagenUrl,
    ];

    // Procesar cambios en la cantidad si es necesario
    if ($request->cantidad != $producto->cantidad) {
        $diferenciaCantidad = $request->cantidad - $producto->cantidad;

        // Si la cantidad aumenta, verificar stock de materias primas
        if ($diferenciaCantidad > 0) {
            DB::beginTransaction();
            try {
                foreach ($producto->materias as $materiaPrima) {
                    $cantidadNecesaria = $materiaPrima->pivot->cantidad * $diferenciaCantidad;
                    if ($materiaPrima->cantidad < $cantidadNecesaria) {
                        DB::rollback();
                        return redirect()->back()->withErrors([
                            'cantidad' => 'No hay suficiente materia prima "' . $materiaPrima->nombre . '" disponible.'
                        ])->withInput();
                    }
                }

                // Descontar la materia prima si hay suficiente stock
                foreach ($producto->materias as $materiaPrima) {
                    $cantidadNecesaria = $materiaPrima->pivot->cantidad * $diferenciaCantidad;
                    $materiaPrima->cantidad -= $cantidadNecesaria;
                    $materiaPrima->save();
                }

                // Añadir cantidad al array de actualización
                $datosParaActualizar['cantidad'] = $request->cantidad;

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return back()->withErrors(['error' => 'Ocurrió un error al actualizar el producto.'])->withInput();
            }
        }
    }

    // Actualizar el producto con los datos preparados
    $producto->update($datosParaActualizar);

    return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente.');
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
