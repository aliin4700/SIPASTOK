<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function addToCart($id)
    {
        return redirect()->route('cart.index');
    }

    public function viewCart()
    {
        return redirect()->route('cart.index');
    }
}
