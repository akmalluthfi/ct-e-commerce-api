<?php

namespace Api;

use Api\Verify;

class TokenController
{
  public static function createToken($created, $id)
  {
    // buat lama expired token selama 10 menit
    $createdTime = strtotime($created);
    $expired = date('Y-m-d H:i:s', strtotime('+10 minutes', $createdTime));
    // buat token 
    $token = hash('sha256', rand(0, 1000));

    // masukkan ke database verify 
    $verify = Verify::create();
    $verify->Token = $token;
    $verify->MemberID = $id;
    $verify->Expired = $expired;

    $verify->write();

    return $token;
  }
}
