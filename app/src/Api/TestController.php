<?php

namespace Api;

use Exception;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\SiteConfig\SiteConfig;

class TestController extends Controller
{
  public function index(HTTPRequest $request)
  {
    $config = SiteConfig::current_site_config();
  }
}
