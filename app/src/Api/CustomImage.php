<?php

namespace Api;

use SilverStripe\Assets\Image;

class CustomImage extends Image
{
  private static $table_name = 'custom_image';

  private static $has_one = [
    'Product' => Product::class
  ];
}
