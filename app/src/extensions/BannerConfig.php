<?php

use Api\CustomImage;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;

class BannerConfig extends DataExtension
{
  private static $has_many = [
    'Banners' => CustomImage::class
  ];

  public function updateCMSFields(FieldList $fields)
  {
    $fields->addFieldToTab('Root.Banner', $banner = UploadField::create('Banner'));

    $banner->getValidator()->setAllowedExtensions(['jpg', 'png']);
    $banner->setFolderName('banner');

    return $fields;
  }
}
