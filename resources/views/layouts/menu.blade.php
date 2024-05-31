@if(auth()->user()->canAny(['ver-rol', 'crear-rol', 'editar-rol', 'borrar-rol', 'ver-producto', 'crear-producto', 'editar-producto', 'borrar-producto', 'ver-log']))
<li class="side-menus {{ Request::is('*') ? 'active' : '' }}">
    @canany(['ver-rol', 'crear-rol', 'editar-rol', 'borrar-rol'])
    <li class="{{ Request::is('home*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="/home">
            <i class="fas fa-tachometer-alt" style="color: #9370DB; margin-right: 8px;"></i>
            <span class="menu-text" style="font-weight: 600; color: #333;">Dashboard</span>
        </a>
    </li>
    <li class="{{ Request::is('usuarios*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="/usuarios">
            <i class="fas fa-users" style="color: #BA55D3; margin-right: 8px;"></i>
            <span class="menu-text" style="font-weight: 600; color: #333;">Usuarios</span>
        </a>
    </li>
    <li class="{{ Request::is('roles*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="/roles">
            <i class="fas fa-user-shield" style="color: #8A2BE2; margin-right: 8px;"></i>
            <span class="menu-text" style="font-weight: 600; color: #333;">Roles</span>
        </a>
    </li>
    @endcanany

    @can('ver-producto')
    <li class="{{ Request::is('productos*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="/productos">
            <i class="fas fa-box-open" style="color: #20B2AA; margin-right: 8px;"></i>
            <span class="menu-text" style="font-weight: 600; color: #333;">Productos</span>
        </a>
    </li>
    @endcan

    @can('ver-materia')
    <li class="{{ Request::is('materias*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="/materias">
            <i class="fas fa-cubes" style="color: #FF6347; margin-right: 8px;"></i>
            <span class="menu-text" style="font-weight: 600; color: #333;">Materia prima</span>
        </a>
    </li>
    @endcan

    @can('ver-ventas')
    <li class="{{ Request::is('ventas*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="/ventas">
            <i class="fas fa-shopping-cart" style="color: #4682B4; margin-right: 8px;"></i>
            <span class="menu-text" style="font-weight: 600; color: #333;">Venta</span>
        </a>
    </li>
    @endcan

    @can('ver-pedido')
    <li class="{{ Request::is('pedidos*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="/pedidos">
            <i class="fas fa-clipboard-list" style="color: #32CD32; margin-right: 8px;"></i>
            <span class="menu-text" style="font-weight: 600; color: #333;">Pedidos</span>
        </a>
    </li>
    @endcan
</ul>
@endif
