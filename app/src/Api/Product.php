<?php

namespace Api;

use SilverStripe\ORM\DataObject;

class Product extends DataObject
{
  private static $table_name = 'products';

  private static $db = [
    'Title' => 'Varchar',
    'Price' => 'Int',
    'isAvailable' => 'Boolean'
  ];

  private static $has_many = [
    'Images' => CustomImage::class,
    'Carts' => Cart::class,
    'OrderDetails' => OrderDetail::class
  ];

  private static $has_one = [
    'Merchant' => Merchant::class
  ];
}
