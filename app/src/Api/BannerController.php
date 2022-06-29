<?php

namespace Api;

use Banner;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

class BannerController extends Controller
{
  public function init()
  {
    parent::init();
    $this->getResponse()->addHeader('Content-Type', 'application/json');
    $this->getResponse()->addHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
    $this->getResponse()->addHeader('Access-Control-Allow-Headers', 'authorization');

    $authorization = explode(' ', $this->getRequest()->getHeader('authorization'));

    if ($authorization[0] !== 'Bearer') return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 401,
      'message' => 'Unauthorized',
    ]));

    $api = Api::get()->filter('Name', 'web')->first();
    // cek apakah didatabase tersedia 
    // jika ada maka lanjutkan, jika tidak return 
    if ($api->Key !== $authorization[1]) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 401,
      'message' => 'Unauthorized',
    ]));
  }

  public function index(HTTPRequest $request)
  {
    if (!is_null($this->response->getBody())) return $this->response;
    if ($request->isGET()) return $this->getBanners($request->getVars());
  }

  public function getBanners($params)
  {
    $banners = Banner::get();

    if (isset($params['limit'])) {
      if (!is_numeric($params['limit'])) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'Bad Request: query param limit must be a number',
      ]));

      $banners = $banners->limit($params['limit']);
    }

    $data = [];
    foreach ($banners as $banner) {
      array_push($data, [
        'picture_url' => $banner->picture()->absoluteLink(),
        'link' => $banner->ReleatedLink,
        'title' => $banner->Title
      ]);
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'Success get Banners',
      'banners' => $data
    ]));
  }
}
