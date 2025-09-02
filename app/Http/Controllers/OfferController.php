<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index()
    {
        return view('offers.index'); // create offers/index.blade.php
    }
}

