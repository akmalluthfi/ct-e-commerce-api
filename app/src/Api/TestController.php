<?php

namespace Api;

use Exception;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

class TestController extends Controller
{
  public function index(HTTPRequest $request)
  {
    $file = $request->postVar('image');
    // var_dump($file);
    // die();
    try {
      // coba upload gambar 
      $image = Image::create();

      $upload = Upload::create();
      $upload->loadIntoFile($file, $image, 'user-profile/default');
      $upload->getValidator()->setAllowedExtensions(['jpg', 'jpeg', 'png']);
    } catch (Exception $e) {
      var_dump($e);
      die();
    }

    die();
  }
}
