<?php

namespace Api;

use Api\Order;
use Api\Product;
use Api\MerchantCategory;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridFieldFooter;
use SilverStripe\Forms\GridField\GridFieldPaginator;

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

  public function getCMSFields()
  {
    $fields = FieldList::create(TabSet::create('Root'));

    $fields->addFieldsToTab('Root.Profile', [
      ReadonlyField::create('FirstName', 'Name'),
      ReadonlyField::create('Email'),
      ReadonlyField::create('isOpen', 'Status')->setDescription('status merchant 1 for open and 0 for close'),
      $dropdown = DropdownField::create('isApproved', 'isApproved', [
        0 => 'No',
        1 => 'Yes'
      ]),
    ]);
    $dropdown->setDescription('you can edit this merchat approved or not');

    $config = GridFieldConfig_RecordViewer::create();
    $config->removeComponentsByType([
      GridFieldPageCount::class,
      GridFieldPaginator::class
    ]);

    $fields->addFieldsToTab('Root.Products', [
      GridField::create(
        'Products',
        'Products List',
        $this->Products(),
        $config
      )
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
