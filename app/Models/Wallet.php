<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'account_number',
        'currency'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getTransactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public static function generateUniqueAccountNumber()
    {
        do {
            $randomNumber = now()->format('Y') . Str::random(6);
            $accNum = "DIGI$randomNumber";
            $accNumExists = DB::table('wallets')->where('account_number', $accNum)->exists();
        } while ($accNumExists);
        return $accNum;
    }
}
