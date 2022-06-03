<?php

use SilverStripe\ORM\DataObject;

class Token extends DataObject
{
  private static $table_name = 'token';

  private static $db = [
    'MemberID' => 'Int',
    'Token' => 'Varchar'
  ];
}
