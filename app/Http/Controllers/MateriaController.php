<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MateriaController extends Controller
{
    // Mostrar una lista de todas las materias
    public function index()
    {
        $materias = Materia::paginate(10);
        return view('materias.index', compact('materias'));
    }

    // Mostrar el formulario para crear una nueva materia
    public function create()
    {
        return view('materias.crear');
    }

    public function store(Request $request)
    {
        // Validación
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'proveedor' => 'required|string|max:255',
            'cantidad' => 'required|numeric|min:0',
            'precio' => 'required|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.mimes' => 'El archivo debe ser de tipo: jpeg, png, jpg, gif o svg.',
            'imagen.max' => 'El tamaño máximo del archivo es de 2 MB.',
        ]);

        // Manejo de la imagen
        $imagenUrl = null;
        if ($request->hasFile('imagen') && $request->file('imagen')->isValid()) {
            $imagen = $request->file('imagen');
            $imagenUrl = $imagen->store('imagenes_materias', 'public'); // Cambiamos la carpeta de almacenamiento
        }
        \Log::info('Archivo recibido: ', ['archivo' => $request->file('imagen')]);


        DB::beginTransaction();
        try {
            // Crear la materia
            $materia = Materia::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'proveedor' => $request->proveedor,
                'cantidad' => $request->cantidad,
                'precio' => $request->precio,
                'imagen_url' => $imagenUrl,
            ]);

            DB::commit();
            return redirect()->route('materias.index')->with('success', 'Materia creada exitosamente.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Ocurrió un error al crear la materia: ' . $e->getMessage()])->withInput();
        }
    }
   


    // Mostrar una materia específica
    public function show(Materia $materia)
    {
        return view('materias.show', compact('materia'));
    }

    // Mostrar el formulario para editar una materia existente
    public function edit(Materia $materia)
    {
        return view('materias.editar', compact('materia'));
    }

    // Actualizar una materia existente en la base de datos
    public function update(Request $request, Materia $materia)
    {
        // Validación (similar al store, pero sin 'required' en imagen)
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'proveedor' => 'required|string|max:255',
            'cantidad' => 'required|numeric|min:0',
            'precio' => 'required|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'imagen.image' => 'El archivo debe ser una imagen válida.',
            'imagen.mimes' => 'El archivo debe ser de tipo: jpeg, png, jpg, gif o svg.',
            'imagen.max' => 'El tamaño máximo del archivo es de 2 MB.',
        ]);
    
        // Manejo de la imagen (similar al store, pero con eliminación de la imagen anterior si existe)
        $imagenUrl = $materia->imagen_url; // Mantener la imagen actual por defecto
        if ($request->hasFile('imagen') && $request->file('imagen')->isValid()) {
            if ($materia->imagen_url) {
                Storage::disk('public')->delete($materia->imagen_url); // Eliminar la imagen anterior
            }
            $imagen = $request->file('imagen');
            $imagenUrl = $imagen->store('imagenes_materias', 'public');
        }
    
        DB::beginTransaction();
        try {
            // Actualizar la materia
            $materia->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'proveedor' => $request->proveedor,
                'cantidad' => $request->cantidad,
                'precio' => $request->precio,
                'imagen_url' => $imagenUrl, // Actualizar la URL de la imagen (si se cambió)
            ]);
    
            DB::commit();
            return redirect()->route('materias.index')->with('success', 'Materia actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la materia: ' . $e->getMessage()])->withInput();
        }
    }
    

    // Eliminar una materia de la base de datos
    public function destroy(Materia $materia)
    {
        try {
            $materia->delete();
            return redirect()->route('materias.index')
                             ->with('success', 'Materia eliminada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error eliminando materia: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error eliminando materia: ' . $e->getMessage()]);
        }
    }
}
