<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Order extends Model
{
    protected $fillable = [
        'invoice_id',
        'invoice',
        'product_id',
        'product_name',
        'image',
        'qty',
        'price'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}


