<?php

namespace Api;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;

class TestController extends Controller
{
  public function index()
  {
    $email = Email::create();

    $email->setHTMLTemplate('Email\\sendOtp');
    $email->setFrom('no-reply@shop.com', 'Admin');
    $email->setTo('akmalluthfi19@gmail.com', 'Akmal Luthfi');
    $email->setSubject('Validation OTP');

    $result = $email->send();


    var_dump($result);
    die();
  }
}
