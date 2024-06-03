<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas</title>
</head>
<body>
    <h1>Reporte de Ventas</h1>
    @if(isset($fecha_inicio) && isset($fecha_fin))
        <p>Desde: {{ $fecha_inicio }} hasta {{ $fecha_fin }}</p>
    @else
        <p>Fecha: {{ now()->toDateString() }}</p>
    @endif
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Descripción</th>
                <th>Total</th>
                <th>Productos Vendidos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $venta)
                <tr>
                    <td>{{ $venta->id }}</td>
                    <td>{{ $venta->descripcion }}</td>
                    <td>${{ number_format($venta->total, 2) }}</td>
                    <td>
                        <table border="1">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($venta->productos as $producto)
                                    <tr>
                                        <td>{{ $producto->nombre }}</td>
                                        <td>{{ $producto->pivot->cantidad }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2">Total de Ventas</td>
                <td colspan="2">${{ number_format($total, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
