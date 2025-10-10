<?php

namespace App\Http\Controllers;

use App\Models\Tcb;

class TcbController extends Controller
{
    public function index()
    {
        // Get all records from tcb table
        $tcbs = Tcb::all();

        // Return as JSON (you can customize to return a view if you want)
        return response()->json($tcbs);
    }
}
