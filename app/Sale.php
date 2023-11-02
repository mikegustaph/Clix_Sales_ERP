<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable =[
        "reference_no", "user_id", "cash_register_id", "table_id", "queue", "customer_id", "warehouse_id", "biller_id", "item", "total_qty", "total_discount", "total_tax", "total_price", "order_tax_rate", "order_tax", "order_discount_type", "order_discount_value", "order_discount", "coupon_id", "coupon_discount", "shipping_cost", "grand_total", "currency_id", "exchange_rate", "sale_status", "payment_status", "paid_amount", "document", "state", "sale_note", "staff_note", "created_at", "woocommerce_order_id"
    ];

    public function biller()
    {
        return $this->belongsTo('App\Biller');
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Warehouse');
    }

    public function table()
    {
        return $this->belongsTo('App\table');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function currency()
    {
        return $this->belongsTo('App\Currency');
    }
}
