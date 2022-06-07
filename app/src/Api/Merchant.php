<?php

namespace Api;

use MerchantCategory;
use Order;
use Product;
use SilverStripe\Security\Member;

class Merchant extends Member
{
  private static $table_name = 'merchants';

  private static $db = [
    'isOpen' => 'Boolean',
    'isApproved' => 'Boolean',
    'isValidated' => 'Boolean'
  ];

  private static $has_one = [
    'Picture' => Image::class,
    'Category' => MerchantCategory::class
  ];

  private static $has_many = [
    'Products' => Product::class,
    'Orders' => Order::class
  ];
}
