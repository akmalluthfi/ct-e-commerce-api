<?php

namespace Api;

use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;

class TestController extends Controller
{
  public function index()
  {
    var_dump(Director::baseURL());
    var_dump(BASE_URL);
    var_dump(BASE_PATH);
    var_dump(Director::baseFolder());

    die();
  }
}
