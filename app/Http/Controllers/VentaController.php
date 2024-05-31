<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF; // Usa el alias registrado


class VentaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-ventas', ['only' => ['index', 'show']]);
        $this->middleware('permission:crear-ventas', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-ventas', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-ventas', ['only' => ['destroy']]);
    }   
    
    public function generarPDF($id)
    {
        $venta = Venta::findOrFail($id);
        $productos = $venta->productos;

        $pdf = PDF::loadView('ventas.pdf', compact('venta', 'productos'));

        return $pdf->download('venta_'.$venta->id.'.pdf');
    }

    public function __invoke()
    {
        return view('ventas.crear');
    }

    public function index()
    {
        $ventas = Venta::with('productos')->paginate(5);
        return view('ventas.index', compact('ventas'));
    }
    public function detalles($id)
{
    $venta = Venta::find($id);
    $detalles = $venta->productos->map(function ($producto) {
        return [
            'producto' => $producto->nombre, // Ajusta esto al nombre del atributo del producto
            'cantidad' => $producto->pivot->cantidad,
        ];
    });
    return response()->json($detalles);
}



    public function create()
    {
        $productos = Producto::all();

        return view('ventas.crear', compact('productos'));
    }

    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        // Validar los datos
        $validated = $request->validate([
            'descripcion' => 'required|string|max:255',
            'total' => 'required|numeric',
            'productos' => 'required|array',
            'productos.*.id' => 'required|integer|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1'
        ]);

        // Crear la venta
        $venta = Venta::create([
            'descripcion' => $request->descripcion,
            'total' => $request->total
        ]);

        // Adjuntar productos a la venta y actualizar la cantidad de los productos
        foreach ($request->productos as $producto) {
            $venta->productos()->attach($producto['id'], ['cantidad' => $producto['cantidad']]);

            // Actualizar la cantidad del producto
            $productoModel = Producto::find($producto['id']);
            if ($productoModel) {
                $productoModel->cantidad -= $producto['cantidad'];
                $productoModel->save();
            }
        }

        DB::commit();
        return redirect()->route('ventas.index')->with('success', 'Venta registrada exitosamente.');
        // return response()->json(['message' => 'Venta registrada exitosamente'], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollback();
        return back()->withErrors(['error' => 'Error al registrar la venta'])->withInput();
        // return response()->json(['message' => 'Error al registrar la venta', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        DB::rollback();
        return back()->withErrors(['error' => 'Error al registrar la venta'])->withInput();
        // return response()->json(['message' => 'Error al registrar la venta', 'error' => $e->getMessage()], 500);
    }
}
    
    public function show(Venta $venta)
    {
        return view('ventas.show', compact('venta'));
    }

    public function edit(Venta $venta)
    {
        return view('ventas.editar', compact('venta'));
    }

    public function update(Request $request, Venta $venta)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
            'productos' => 'required|array',
            'productos.*.cantidad' => 'required|integer|min:1'
        ]);
    
        DB::beginTransaction();
        try {
            $total = 0;
    
            // Recorrer cada producto enviado desde el formulario
            foreach ($request->productos as $id => $details) {
                $producto = Producto::findOrFail($id);
                $cantidadOriginal = $venta->productos()->find($id)->pivot->cantidad;
                $nuevaCantidad = $details['cantidad'];
                $diferenciaCantidad = $nuevaCantidad - $cantidadOriginal;
    
                // Actualizar el pivot con la nueva cantidad
                $venta->productos()->updateExistingPivot($id, ['cantidad' => $nuevaCantidad]);
                
                // Recalcular el total
                $total += $producto->precio * $nuevaCantidad;
    
                // Actualizar la cantidad de stock del producto
                $producto->cantidad -= $diferenciaCantidad;
                $producto->save();
            }
    
            // Actualizar la venta con el nuevo total y descripción
            $venta->update([
                'descripcion' => $request->descripcion,
                'total' => $total
            ]);
    
            DB::commit();
            return redirect()->route('ventas.index')->with('success', 'Venta actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Venta $venta)
    {
        $venta->delete();
        return redirect()->route('ventas.index')->with('success', 'Venta eliminada exitosamente.');
    }
}