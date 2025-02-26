<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function history()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User Not Found',
                ], 404);
            }
            $transactions = $user->getTransactions->map(function ($transaction) use ($user) {
                return [
                    'user_name' => $user->name,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'created_at' => $transaction->created_at->format('Y-m-d'),
                ];
            });
            if (empty($transactions)) {
                return response()->json([
                    'status'  => 'info',
                    'message' => 'No Transactions Made by the User',
                ], 400);
            }
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaction fetched successfully',
                'transactions' => $transactions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to Fetch Balance Details',
                'status'  => 'error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
