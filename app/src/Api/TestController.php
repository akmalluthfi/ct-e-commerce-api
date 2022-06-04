<?php

namespace Api;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;

class TestController extends Controller
{
  public function index()
  {
    $email = new Email();
    $email->send();
    var_dump('Email sending.... please check your email');
    die();
  }
}
