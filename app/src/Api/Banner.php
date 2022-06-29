<?php

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\TextField;

class Banner extends DataObject
{
  private static $table_name = 'banners';

  private static $db = [
    'ReleatedLink' => 'Varchar',
    'Title' => 'Varchar'
  ];

  private static $has_one = [
    'Picture' => Image::class
  ];

  private static $summary_fields = [
    'Picture.CMSThumbnail' => 'Picture',
    'Title' => 'Title',
    'ReleatedLink' => 'Releated Link',
  ];

  public function getCMSFields()
  {
    $fields =  FieldList::create(
      TextField::create('ReleatedLink'),
      TextField::create('Title'),
      $uploader = UploadField::create('Picture')
    );

    $uploader->setFolderName('banners');
    $uploader->getValidator()->setAllowedExtensions(['png', 'jpeg', 'jpg']);

    return $fields;
  }
}
