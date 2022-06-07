<?php

namespace Api;

use Api\Merchant;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;

class MerchantCategory extends DataObject
{
  private static $table_name = 'categories';

  private static $db = [
    'Name' => 'Varchar'
  ];

  private static $has_many = [
    'Merchants' => Merchant::class
  ];

  public function getCMSFields()
  {
    $fields = FieldList::create(TabSet::create('Root'));

    $fields->addFieldToTab('Root.Main', TextField::create('Name'), 'MerchantCategory');

    return $fields;
  }
}
