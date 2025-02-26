<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function getWallet()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User Not Found',
                ], 404);
            }

            $wallet =
                [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'wallet_id' => $user?->wallet?->id,
                    'balance' => $user?->wallet?->balance,
                    'currency' =>  $user?->wallet?->currency,
                ];

            return response()->json([
                'status' => 'success',
                'Wallet Details' => $wallet
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Can Not Fetch the wallet details',
                'status' => 'success',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function balance()
    {
        try {

            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User Not Found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'wallet_balance' => $user?->wallet?->balance ?? 0,
                'currency' => $user?->wallet?->currency ?? ''
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to Fetch Balance Details',
                'status'  => 'error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deposit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation error',
                    'error'   => $validator->errors(),
                ], 422);
            }
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User Not Found',
                ], 404);
            }
            $wallet = $user->wallet;
            $wallet->balance += $request->amount;
            $wallet->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $request->amount,
                'currency' => $user?->wallet?->currency
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Amount Credited in your account',
                'balance' => $user->wallet->balance,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to Add Amount Into Wallet',
                'status'  => 'error',
                'error'   => $e->getMessage(),
            ]);
        }
    }
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'error'   => $validator->errors(),
            ], 422);
        }
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User Not Found',
            ], 404);
        }

        if ($user?->wallet?->balance < $request->amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient funds'
            ], 400);
        }

        $user->wallet->balance -= $request->amount;
        $user->wallet->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Withdrawal successful',
            'new_balance' => $user?->wallet?->balance ?? 0
        ], 200);
    }
    public function transfer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'receiver_account_number' => 'required|string',
                'amount' => 'required|numeric|min:1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation error',
                    'error'   => $validator->errors(),
                ], 422);
            }
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User Not Found',
                ], 404);
            }

            $senderWallet   = $user->wallet;
            $receiverWallet = Wallet::where('account_number', $request->receiver_account_number)->first();

            if (!$receiverWallet) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User Wallet Not Found',
                ], 404);
            }

            if ($senderWallet->account_number === $receiverWallet->account_number) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'You Cannot send amount to your own account',
                ], 400);
            }

            if ($this->isSuspiciousTransaction($senderWallet->id, $request->amount)) {
                return response()->json(['error' => 'Suspicious transaction detected! Contact support.'], 403);
            }

            if ($senderWallet->balance < $request->amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient balance'
                ], 400);
            }

            $dailyTotal = Transaction::where('user_id', $user->id)
                ->whereDate('created_at', Carbon::today())
                ->sum('amount');

            if ($dailyTotal + $request->amount > 10000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Daily transaction limit exceeded'
                ], 400);
            }

            $senderWallet->balance -= $request->amount;
            $receiverWallet->balance += $request->amount;

            $senderWallet->save();
            $receiverWallet->save();

            Transaction::create(['user_id' => $user->id, 'type' => 'debit', 'amount' => $request->amount, 'currency' => $senderWallet->currency]);
            Transaction::create(['user_id' => $receiverWallet->id, 'type' => 'credit', 'amount' => $request->amount, 'currency' => $receiverWallet->currency]);

            return response()->json([
                'status' => 'success',
                'message' => 'Amount Transferd successful'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to Fetch Balance Details',
                'status'  => 'error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function isSuspiciousTransaction($userId, $amount)
    {

        $highValueThreshold = 10000;
        $transactionCountLimit = 3;
        $timeFrameMinutes = 10;

        if ($amount >= $highValueThreshold) {
            return true;
        }

        $recentTransactions = Transaction::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subMinutes($timeFrameMinutes))
            ->count();

        if ($recentTransactions >= $transactionCountLimit) {
            return true;
        }

        return false;
    }
}
