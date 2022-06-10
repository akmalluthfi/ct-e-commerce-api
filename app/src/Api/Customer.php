<?php

namespace Api;

use Api\Cart;
use Api\Order;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ReadonlyField;

class Customer extends Member
{
  private static $table_name = 'customers';

  private static $db = [
    'isValidated' => 'Boolean'
  ];

  private static $has_one = [
    'Picture' => Image::class
  ];

  private static $has_many = [
    'Carts' => Cart::class,
    'Orders' => Order::class
  ];

  public function name()
  {
    return $this->FirstName . ' ' . $this->LastName;
  }

  public function getCMSFields()
  {
    $fields = FieldList::create(TabSet::create('Root'));

    $fields->addFieldsToTab('Root.Main', [
      ReadonlyField::create('FirstName'),
      ReadonlyField::create('LastName'),
      ReadonlyField::create('Email'),
    ]);

    return $fields;
  }
}
