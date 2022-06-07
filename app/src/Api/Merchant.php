<?php

namespace Api;

use Api\Order;
use Api\Product;
use Api\MerchantCategory;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ReadonlyField;

class Merchant extends Member
{
  private static $table_name = 'merchants';

  private static $db = [
    'isOpen' => 'Boolean',
    'isApproved' => 'Boolean',
    'isValidated' => 'Boolean',
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
      ReadonlyField::create('FirstName', 'Name'),
      ReadonlyField::create('Email'),
      ReadonlyField::create('isOpen', 'Status'),
      CheckboxField::create('isApproved', 'isApproved')->setDescription('is approved for this merchants'),
    ]);

    return $fields;
  }

  public function summaryFields()
  {
    return [
      'FirstName' => 'Name',
      'Email' => 'Email',
      'isOpen' => 'Status',
      'isApproved' => 'isApproved',
      'isValidated' => 'isValidated'
    ];
  }
}
