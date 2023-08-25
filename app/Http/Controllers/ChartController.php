<?php

namespace App\Http\Controllers;

use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class ChartController extends Controller
{
    public function index()
    {
        return view('chart');
    }

    public function store(Request $request)
    {

            $income = new Income();
            $income->amount = $request->amount;
            $income->save();

            return back();
    }

    public function chart_data(Request $request)
    {
        $monthsAgo = $request->query('month', 12); 

        // $data = Cache::remember('income_cache', 60, function () use ($monthsAgo){
            $data = Income::orderBy('id', 'desc')->take($monthsAgo)
            ->get();
    
            $amounts = $data->pluck('amount')->toArray();
            $dates = $data->pluck('created_at')->map(function ($date) {
                return $date->format('Y-m-d');
            })->toArray();
    
            return [
                'labels' => array_reverse($dates), 
                'data' => array_reverse($amounts),
            ];
        // });
    
        return $data;
    }
}
