<?php

namespace Api;

use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;

class CustomImage extends Image
{
  private static $table_name = 'custom_image';

  private static $has_one = [
    'Product' => Product::class
  ];

  private static $summary_fields = [
    'renderImg' => 'Images',
    'Name' => 'Name'
  ];

  public function renderImg()
  {
    return $this->ScaleWidth(100);
  }
}
