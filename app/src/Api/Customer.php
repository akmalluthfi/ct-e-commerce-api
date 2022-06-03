<?php

namespace Api;

use Order;
use SilverStripe\Security\Member;

class Customer extends Member
{
  private static $table_name = 'customers';

  private static $has_one = [
    'Picture' => Image::class
  ];

  private static $has_many = [
    'Carts' => Cart::class,
    'Orders' => Order::class
  ];
}
