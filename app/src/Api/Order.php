<?php

namespace Api;

use SilverStripe\ORM\DataObject;

class Order extends DataObject
{
  private static $table_name = 'orders';

  private static $db = [
    'Total' => 'Int',  //total semua barang 
    'Status' => 'Int' // 0 => pending, 1 => accepted, 2 => rejected
  ];

  private static $has_one = [
    'Customer' => Customer::class,
    'Merchant' => Merchant::class
  ];

  private static $has_many = [
    'OrderDetails' => OrderDetail::class //order per product
  ];

  public function getStatus()
  {
    if ($this->record['Status'] === 0) {
      return 'Pending';
    } else if ($this->record['Status'] === 1) {
      return 'Accepted';
    } else if ($this->record['Status'] === 2) {
      return 'Rejected';
    }
  }
}
