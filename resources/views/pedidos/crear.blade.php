@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Nuevo Pedido</h3>
    </div>
    <div class="section-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Detalles del Pedido
                    </div>
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="card-body">
                        <form id="ventaForm" action="{{ route('pedidos.store') }}" method="POST">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="descripcion">Descripción</label>
                                    <input type="text" class="form-control" id="descripcion" name="descripcion"
                                        placeholder="Descripción breve del pedido" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="producto">Producto</label>
                                    <select id="producto" class="form-control">
                                        @foreach ($productos as $producto)
                                        <option value="{{ $producto->id }}" data-precio="{{ $producto->precio }}" data-cantidad="{{ $producto->cantidad }}">
                                            {{ $producto->nombre }} - ${{ number_format($producto->precio, 2) }} - <span class="cantidad-disponible">{{ $producto->cantidad }}</span>
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="cantidad">Cantidad</label>
                                    <input type="number" class="form-control" id="cantidad">
                                </div>
                                <div class="form-group col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-success btn-block"
                                        id="addProductButton">Agregar</button>
                                </div>
                            </div>
                            <div id="productosContainer"></div>
                            <input type="hidden" name="total" id="total">
                        </form>
                        <table class="table table-striped mt-2" id="productosTable">
                            <thead>
                                <tr>
                                    <th>Opciones</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                        <h4>Total: $<span id="totalDisplay">0.00</span></h4>
                        <button type="submit" form="ventaForm" class="btn btn-primary">Guardar Pedido</button>
                        <a href="{{ route('pedidos.index') }}" class="btn btn-danger">Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('addProductButton').addEventListener('click', agregarProducto);
    cargarProductosDeLocalStorage();
    actualizarCantidadesDisponibles();

    document.getElementById('ventaForm').addEventListener('submit', function(event) {
        actualizarFormulario();
    });

    function agregarProducto() {
        const productoSelect = document.getElementById('producto');
        const selectedOption = productoSelect.options[productoSelect.selectedIndex];
        const productoId = selectedOption.value;
        const productoNombre = selectedOption.text.split(' - $')[0];
        const precio = parseFloat(selectedOption.getAttribute('data-precio'));
        const cantidadDisponible = parseInt(selectedOption.getAttribute('data-cantidad'));
        const cantidadInput = document.getElementById('cantidad');
        const cantidad = parseInt(cantidadInput.value);
        const subtotal = precio * cantidad;

        if (cantidad <= 0 || isNaN(cantidad)) {
            alert('Ingrese una cantidad válida.');
            return;
        }

        if (cantidad > cantidadDisponible) {
            alert('La cantidad ingresada excede la cantidad disponible del producto.');
            return;
        }

        const producto = {
            id: productoId,
            nombre: productoNombre,
            cantidad: cantidad,
            precio: precio,
            subtotal: subtotal.toFixed(2)
        };

        // Descontar la cantidad agregada del producto en la vista y en el localStorage
        selectedOption.setAttribute('data-cantidad', cantidadDisponible - cantidad);
        const cantidadDisponibleSpan = selectedOption.querySelector('.cantidad-disponible');
        if (cantidadDisponibleSpan) {
            cantidadDisponibleSpan.textContent = cantidadDisponible - cantidad;
        }

        guardarEnLocalStorage(producto);
        actualizarCantidadesEnLocalStorage(productoId, cantidadDisponible - cantidad);
        cantidadInput.value = '';
    }

    function guardarEnLocalStorage(producto) {
        let productos = JSON.parse(localStorage.getItem('productosVenta')) || [];
        let productoExistente = productos.find(p => p.id === producto.id);

        if (productoExistente) {
            productoExistente.cantidad += producto.cantidad;
            productoExistente.subtotal = (productoExistente.cantidad * productoExistente.precio).toFixed(2);
        } else {
            productos.push(producto);
        }

        localStorage.setItem('productosVenta', JSON.stringify(productos));
        actualizarTabla();
        actualizarTotal();
        actualizarFormulario();
    }

    function actualizarCantidadesEnLocalStorage(productoId, nuevaCantidad) {
        let cantidades = JSON.parse(localStorage.getItem('cantidadesDisponibles')) || {};
        cantidades[productoId] = nuevaCantidad;
        localStorage.setItem('cantidadesDisponibles', JSON.stringify(cantidades));
    }

    function cargarProductosDeLocalStorage() {
        const productos = JSON.parse(localStorage.getItem('productosVenta')) || [];
        productos.forEach(agregarFilaATabla);
        actualizarTotal();
    }

    function actualizarCantidadesDisponibles() {
        const cantidades = JSON.parse(localStorage.getItem('cantidadesDisponibles')) || {};
        const productoSelect = document.getElementById('producto').options;

        for (let i = 0; i < productoSelect.length; i++) {
            const option = productoSelect[i];
            const productoId = option.value;
            if (cantidades[productoId] !== undefined) {
                const cantidadDisponible = cantidades[productoId];
                option.setAttribute('data-cantidad', cantidadDisponible);
                const cantidadDisponibleSpan = option.querySelector('.cantidad-disponible');
                if (cantidadDisponibleSpan) {
                    cantidadDisponibleSpan.textContent = cantidadDisponible;
                }
            }
        }
    }

    window.removeProduct = function(element, id) {
        var row = element.closest('tr');
        if (!row) {
            console.error("No se encontró la fila.");
            return;
        }

        row.remove();

        let productos = JSON.parse(localStorage.getItem('productosVenta')) || [];
        productos = productos.filter(producto => producto.id.toString() !== id.toString());
        localStorage.setItem('productosVenta', JSON.stringify(productos));

        actualizarTabla();
        actualizarTotal();
        actualizarFormulario();
        actualizarCantidadesDisponibles();
    }

    function actualizarTabla() {
        const tbody = document.getElementById('productosTable').querySelector('tbody');
        tbody.innerHTML = '';

        let productos = JSON.parse(localStorage.getItem('productosVenta')) || [];
        productos.forEach(producto => agregarFilaATabla(producto));
    }

    function agregarFilaATabla(producto) {
        const tbody = document.getElementById('productosTable').querySelector('tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeProduct(this, '${producto.id}')">Eliminar</button></td>
        <td>${producto.nombre}</td>
        <td>${producto.cantidad}</td>
        <td>$${producto.precio}</td>
        <td>$${producto.subtotal}</td>
        `;
        tbody.appendChild(row);
    }

    function actualizarTotal() {
        let productos = JSON.parse(localStorage.getItem('productosVenta')) || [];
        let total = productos.reduce((sum, producto) => sum + parseFloat(producto.subtotal), 0);
        document.getElementById('totalDisplay').textContent = total.toFixed(2);
        document.getElementById('total').value = total.toFixed(2);
    }

    function actualizarFormulario() {
        const productosContainer = document.getElementById('productosContainer');
        productosContainer.innerHTML = '';

        let productos = JSON.parse(localStorage.getItem('productosVenta')) || [];
        productos.forEach(producto => {
            let inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'productos[' + producto.id + '][id]';
            inputId.value = producto.id;

            let inputCantidad = document.createElement('input');
            inputCantidad.type = 'hidden';
            inputCantidad.name = 'productos[' + producto.id + '][cantidad]';
            inputCantidad.value = producto.cantidad;

            productosContainer.appendChild(inputId);
            productosContainer.appendChild(inputCantidad);
        });
    }

    // Limpiar localStorage si la venta fue exitosa
    @if(session('venta_exitosa'))
        localStorage.removeItem('productosVenta');
        localStorage.removeItem('cantidadesDisponibles');
    @endif
});
</script>
    