<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    // Especifica el nombre de la tabla si no sigue la convención de nombres de Laravel
    protected $table = 'pedidos';

    // Desactiva los timestamps si tu tabla no tiene las columnas created_at y updated_at
    public $timestamps = true;


    // Especifica qué campos pueden ser asignados masivamente
    protected $fillable = [
        'id_pedido',
        'nombre',
        'ciente',
        'total',
        'descripcion',
    ];



    

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'ped_prod','id_pedido','id_producto');
    }

    public function puntoventas()
    {
        return $this->hasMany(Puntoventa::class, 'id_punventa');
    }
   
    

    
    
}
