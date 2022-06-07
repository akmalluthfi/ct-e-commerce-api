<?php

namespace Api;

use MerchantCategory;
use Order;
use Product;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Security\Member;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CheckboxField;

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

  /**
   * CMS Fields
   * @return FieldList
   */
  public function getCMSFields()
  {
    $fields = FieldList::create(TabSet::create('Root'));

    $fields->addFieldsToTab('Root.Main', [
      CheckboxField::create('isApproved', 'Approved'),
    ]);

    return $fields;
  }
}
