<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class PedidoController extends Controller
{
    public function __invoke()
    {
        return view('pedidos.crear');
    }

    public function generarPDF($id)
    {
        $pedido = Pedido::findOrFail($id);
        $productos = $pedido->productos;

        $pdf = PDF::loadView('pedidos.pdf', compact('pedido', 'productos'));

        return $pdf->download('pedido_'.$pedido->id.'.pdf');
    }

    public function index()
    {
        $pedidos = Pedido::with('productos')->paginate(5);
        return view('pedidos.index', compact('pedidos'));
    }
    public function detalles($id)
{
    $pedido = Pedido::find($id);
    $detalles = $pedido->productos->map(function ($producto) {
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
        return view('pedidos.crear', compact('productos'));
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
        $pedido = Pedido::create([
            'descripcion' => $request->descripcion,
            'total' => $request->total
        ]);

        // Adjuntar productos a la venta y actualizar la cantidad de los productos
        foreach ($request->productos as $producto) {
            $pedido->productos()->attach($producto['id'], ['cantidad' => $producto['cantidad']]);

            // Actualizar la cantidad del producto
            $productoModel = Producto::find($producto['id']);
            if ($productoModel) {
                $productoModel->cantidad -= $producto['cantidad'];
                $productoModel->save();
            }
        }

        DB::commit();
        return redirect()->route('pedidos.index')->with('success', 'Pedido registrada exitosamente.');
        // return response()->json(['message' => 'Venta registrada exitosamente'], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollback();
        return back()->withErrors(['error' => 'Error al registrar el pedido'])->withInput();
        // return response()->json(['message' => 'Error al registrar la venta', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        DB::rollback();
        return back()->withErrors(['error' => 'Error al registrar el pedido'])->withInput();
        // return response()->json(['message' => 'Error al registrar la venta', 'error' => $e->getMessage()], 500);
    }
}

    



    public function show(Pedido $pedido)
    {
        return view('pedidos.show', compact('pedido'));
    }

    public function edit(Pedido $pedido)
    {
        return view('pedidos.editar', compact('pedido'));
    }

    public function update(Request $request, Pedido $pedido)
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
                $cantidadOriginal = $pedido->productos()->find($id)->pivot->cantidad;
                $nuevaCantidad = $details['cantidad'];
                $diferenciaCantidad = $nuevaCantidad - $cantidadOriginal;
    
                // Actualizar el pivot con la nueva cantidad
                $pedido->productos()->updateExistingPivot($id, ['cantidad' => $nuevaCantidad]);
                
                // Recalcular el total
                $total += $producto->precio * $nuevaCantidad;
    
                // Actualizar la cantidad de stock del producto
                $producto->cantidad -= $diferenciaCantidad;
                $producto->save();
            }
    
            // Actualizar la venta con el nuevo total y descripción
            $pedido->update([
                'descripcion' => $request->descripcion,
                'total' => $total
            ]);
    
            DB::commit();
            return redirect()->route('pedidos.index')->with('success', 'Pedido actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Pedido $pedido)
    {
        $pedido->delete();
        return redirect()->route('pedidos.index')->with('success', 'Pedido eliminado exitosamente.');
    }
}
