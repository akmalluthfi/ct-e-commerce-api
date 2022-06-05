<?php

namespace Api;

use SilverStripe\ORM\DataObject;

class Verify extends DataObject
{
  private static $table_name = 'verify';

  private static $db = [
    'Token' => 'Varchar',
    'MemberID' => 'Int',
    'Expired' => 'Datetime'
  ];

  public function isExpired()
  {
    $expired = strtotime($this->Expired);
    $now = strtotime(date('Y-m-d H:i:s'));
    if ($expired < $now) return true;
    return false;
  }
}
