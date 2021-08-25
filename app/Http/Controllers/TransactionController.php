<?php

namespace App\Http\Controllers;

use App\Http\Resources\Transaction\TransactionItem;
use App\Models\Option;
use App\Models\Playlist;
use App\Models\Transaction;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query();

        $filters = $request->get('filters', []);
        $statusFilter = Arr::get($filters, 'status');
        $typeFilter = Arr::get($filters, 'type');
        $paymentMethodFilter = Arr::get($filters, 'payment_method');
        $fromFilter = Arr::get($filters, 'from');
        $toFilter = Arr::get($filters, 'to');
        $currencyFilter = Arr::get($filters, 'currency');

        if ($statusFilter){
            $query->where('status', array_flip(Transaction::STATUS_TEXT)[$statusFilter]);
        }

        if ($typeFilter){
            $query->where('type', array_flip(Transaction::TYPE_TEXT)[$typeFilter]);
        }

        if ($paymentMethodFilter){
            $query->where('payment_method_id', $paymentMethodFilter);
        }

        if ($currencyFilter){
            $query->where('currency', $currencyFilter);
        }

        if ($fromFilter){
            $query->where('created_at', '>=', $fromFilter);
        }

        if ($toFilter){
            $query->where('created_at', '<=', $toFilter);
        }

        $transactions = $query->paginate();

        return TransactionItem::collection($transactions);
    }


}
