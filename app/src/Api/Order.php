<?php

namespace Api;

use SilverStripe\ORM\DataObject;

class Order extends DataObject
{
  private static $table_name = 'orders';

  private static $db = [
    'Total' => 'Int' //total semua barang 
  ];

  private static $has_one = [
    'Customer' => Customer::class,
    'Merchant' => Merchant::class
  ];

  private static $has_many = [
    'OrderDetails' => OrderDetail::class //order per product
  ];
}
