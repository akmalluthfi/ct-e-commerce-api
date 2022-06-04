<?php

namespace Api;

use SilverStripe\ORM\DataObject;

class OTP extends DataObject
{
  private static $table_name = 'otp';

  private static $db = [
    'OTP' => 'Int',
    'MemberID' => 'Int',
    'Expired' => 'Datetime'
  ];
}
