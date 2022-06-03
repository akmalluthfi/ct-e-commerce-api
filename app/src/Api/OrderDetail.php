<?php

namespace Api;

use SilverStripe\ORM\DataObject;

class OrderDetail extends DataObject
{
  private static $table_name = 'order_details';

  private static $db = [
    'SubTotal' => 'Int',
    'Quantity' => 'Int',
  ];

  private static $has_one = [
    'Product' => Product::class,
    'Order' => Order::class
  ];
}
