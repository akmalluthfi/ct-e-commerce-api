<?php

use SilverStripe\ORM\DataObject;

class Api extends DataObject
{
  private static $table_name = 'api';

  private static $db = [
    'Name' => 'Varchar',
    'Key' => 'Varchar(255)'
  ];
}
