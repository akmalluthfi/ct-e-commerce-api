<?php

namespace Api;

use Api\Cart;
use Api\Merchant;
use Api\CustomImage;
use Api\OrderDetail;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;

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

  private static $summary_fields = [
    'Title' => 'Title',
    'Price' => 'Price per unit',
    'isAvailable' => 'isAvailable'
  ];

  public function getFirstImage()
  {
    return $this->Images()->first()->absoluteLink();
  }

  public function getCMSFields()
  {
    $fields = FieldList::create(TabSet::create('Root'));

    $fields->addFieldsToTab('Root.Detail', [
      ReadonlyField::create('Title', 'Name of product'),
      ReadonlyField::create('Price', 'Price per unit'),
      DropdownField::create('isAvailable', 'is Available', [
        0 => 'Not Available',
        1 => 'Available'
      ])->setDisabled(true)
    ]);

    $config = GridFieldConfig::create();

    $config->addComponents([
      new GridFieldDataColumns(),
      new GridFieldSortableHeader()
    ]);

    $fields->addFieldToTab('Root.Images', GridField::create('Images', 'Product images', $this->Images(), $config));

    return $fields;
  }
}
