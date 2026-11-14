<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

/**
 * Cart, wishlist, checkout and customer-account screens are not live. They show a clear
 * notice instead of a fake working flow, until the owner enables ordering.
 */
class DemoController extends Controller
{
    private function page(string $title, string $text)
    {
        return response()->view('site.unavailable', ['title' => $title, 'text' => $text]);
    }

    public function cart()
    {
        return $this->page('Cart', 'Online ordering is not available. Please call or message the store to check a product and reserve it.');
    }

    public function wishlist()
    {
        return $this->page('Wishlist', 'Saved lists are not available yet. Browse the catalogue or ask the store.');
    }

    public function compare()
    {
        return $this->page('Compare products', 'Product comparison is not available yet. The pharmacist can help you choose in store.');
    }

    public function checkout()
    {
        return $this->page('Checkout', 'Online checkout and payments are not available. Please call or visit the store.');
    }

    public function login()
    {
        return $this->page('Customer login', 'Customer accounts are not available yet.');
    }

    public function register()
    {
        return $this->page('Create an account', 'Customer accounts are not available yet.');
    }

    public function profile()
    {
        return $this->page('My profile', 'Customer accounts are not available yet.');
    }

    public function orders()
    {
        return $this->page('My orders', 'Online orders are not available yet.');
    }
}
