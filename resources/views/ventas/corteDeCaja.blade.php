<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte de Caja</title>
</head>
<body>
    <h1>Corte de Caja</h1>
    <p>Fecha: {{ \Carbon\Carbon::today()->toDateString() }}</p>
    <p>Monto Inicial del Día: ${{ number_format($montoInicial, 2) }}</p>
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
            @foreach($ventasDelDia as $venta)
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
                <td colspan="2">Total del Día con Monto Inicial</td>
                <td colspan="2">${{ number_format($totalConInicial, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
