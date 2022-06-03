<?php

namespace Api;

use SilverStripe\ORM\DataObject;

class MerchantCategory extends DataObject
{
  private static $table_name = 'categories';

  private static $db = [
    'Title' => 'Varchar'
  ];

  private static $has_many = [
    'Merchants' => Merchant::class
  ];
}
