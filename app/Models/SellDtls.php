<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellDtls extends Model
{
    use HasFactory;
    protected $table = 'sell_dtls';
    protected $primaryKey = 'SellDtID';
    protected $fillable = [
        'ProductID',
        'SellMemoID',
        'Quantity',
        'Rate',
        'SubTotal',
        'isApproved',
        'approved_by',
        'created_by',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID');
    }
    public function sellMemo()
    {
        return $this->belongsTo(SellMemo::class, 'SellMemoID');
    }
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


}
