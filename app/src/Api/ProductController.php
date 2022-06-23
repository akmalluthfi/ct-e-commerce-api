<?php

namespace Api;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;

class ProductController extends Controller
{
  public function init()
  {
    parent::init();
    $this->getResponse()->addHeader("Content-type", "application/json");
    $this->getResponse()->addHeader(
      'Access-Control-Allow-Origin',
      'http://localhost:3000'
    );

    // cek ketersediaan api_key
    $api_key = $this->getRequest()->getHeader('x-api-key');

    if (is_null($api_key)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 401,
      'message' => "Unauthorized",
    ]));

    $api = Api::get()->filter('Name', 'web')->first();
    // cek apakah didatabase tersedia 
    // jika ada maka lanjutkan, jika tidak return 
    if ($api->Key !== $api_key) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 401,
      'message' => "Unauthorized",
    ]));
  }

  public function index(HTTPRequest $request)
  {
    if (!is_null($this->response->getBody())) return $this->response;

    $access_token = $request->getHeader('access-token');
    // cek apakah ada access token 
    if (is_null($access_token)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 401,
      'message' => "Unauthorized",
    ]));

    // jika ada access token 
    // cek apakah valid
    try {
      $decoded = JWT::decode($access_token, new Key(Environment::getEnv('SECRET_KEY'), 'HS256'));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 401,
        'message' => "Unauthorized",
      ]));
    }

    // jika valid,
    // ambil param 
    $id = $request->param('id');
    $resource = $request->param('resource');

    // cek method yang dipakai 
    if ($request->isGET()) {
      // jika tidak ada id yang dikirimkan
      if (is_null($id)) return $this->getAllProducts($request);

      // cek apakah id yang dikirimkan numeric 
      if (!is_numeric($id)) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'id must be int'
      ]));

      // cek apakah ada id yang dikirimkan 
      if (isset($id)) return $this->getSingleProduct($id);
    }

    // cek apakah dia merchant
    // cek apakah id tersebut dari merchant
    $merchant = Merchant::get_by_id($decoded->id);

    // cek jika ada id, apakah id product tersebut milik user 
    if (is_numeric($id)) {
      $products = $merchant->products()->filter('ID', $id)->first();
      // berarti product id tersebut bukan milik user
      if (is_null($products)) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 404,
        'message' => 'Product id not found in this merchant',
      ]));
    }

    // jika bukan dari merchant
    if (is_null($merchant)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 403,
      'message' => 'Forbidden',
    ]));

    if ($request->isPOST()) {
      // jika tidak ada id dan resource yang dikirimkan 
      if (is_null($id) && is_null($resource)) return $this->addProduct($request, $merchant);

      // cek apakah id yang dikirimkan numeric 
      if (!is_numeric($id)) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'id must be int'
      ]));

      if (isset($id) && $resource === 'images') return $this->addProductImage($request, $merchant);
    }

    if ($request->isDELETE()) {
      // cek apakah id yang dikirimkan numeric 
      if (!is_numeric($id)) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'id must be int'
      ]));

      if (is_null($resource)) return $this->deleteProduct($id);

      $resource_id = $request->param('resource_id');
      if (!is_numeric($resource_id)) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'resource id must be int'
      ]));

      if (isset($id) && $resource === 'images' && isset($resource_id)) return $this->deleteProductImage($id, $resource_id);
    }

    if ($request->isPUT()) {
      if (is_numeric($id)) {
        if (is_null($resource)) return $this->editProduct($request, $merchant);

        if ($resource === 'status') return $this->editProductStatus($request, $merchant);
      }
    }

    // jika bukan itu semua 
    return $this->httpError(404);
  }

  public function deleteProduct($id)
  {
    $product = Product::get_by_id($id);
    // sudah dicek, dan pasti ada 
    $product->delete();

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success delete product',
    ]));
  }

  public function editProductStatus(HTTPRequest $request, Merchant $merchant)
  {
    // ambil product 
    $product = Product::get_by_id($request->param('id'));
    // ketersediaan product sudah dicek diindex
    $data = json_decode($request->getBody());

    $product->isAvailable = $data->status;

    try {
      $product->write();
      return $this->getResponse()->setBody(json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'Success change status product',
      ]));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage(),
      ]));
    }
  }

  public function deleteProductImage($product_id, $image_id)
  {
    // ambil product-nya 
    $image = CustomImage::get_by_id($image_id);
    // cek apakah image ini milik id product 
    if ($image->ProductID != $product_id) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'product_id not match with image_id',
    ]));

    $image->delete();
    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success delete image',
    ]));
  }

  public function editProduct(HTTPRequest $request, Merchant $merchant)
  {
    $data = json_decode($request->getBody());
    // cek apakah ada data yang dikirim 
    $title = $data->title;
    $price = $data->price;

    if (is_null($title) || is_null($price)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'parameter required'
    ]));

    // jika ada
    // ambil product 
    $product = Product::get_by_id($request->param('id'));

    // cek apakah product tersedia 
    if (is_null($product)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'product not found'
    ]));

    $product->Title = $title;
    $product->Price = $price;

    try {
      $product->write();
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage()
      ]));
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success change product'
    ]));
  }

  public function addProductImage(HTTPRequest $request, Merchant $merchant)
  {
    // !cek apakah id product tersebut milik merchant ini
    // cek apakah ada image yang dikirim
    if (is_null($image = $request->postVar('image'))) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'param required',
    ]));

    try {
      $this->_uploadImage($image, $request->param('id'));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage(),
      ]));
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success add image for id #' . $request->param('id'),
    ]));
  }

  public function getSingleProduct($id)
  {
    $product = Product::get_by_id($id);

    if (is_null($product)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'product not found',
    ]));

    $resource = [
      'id' => $product->ID,
      'title' => $product->Title,
      'price' => $product->Price,
      'isAvailable' => $product->isAvailable,
      'images' => [],
      'merchant' => [
        'id' => $product->merchant()->ID,
        'name' => $product->merchant()->Name,
        'category' => $product->merchant()->category()->Name
      ]
    ];

    foreach ($product->images() as $image) {
      array_push($resource['images'], $image->AbsoluteLink());
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success get product with id #' . $id,
      'product' => $resource
    ]));
  }

  public function getAllProducts(HTTPRequest $request)
  {
    // untuk sekarang tidak membutuhkan filter, dll
    $products = Product::get();

    $resource = [];

    foreach ($products as $key => $product) {
      array_push($resource, [
        'id' => $product->ID,
        'title' => $product->Title,
        'price' => $product->Price,
        'isAvailable' => $product->isAvailable,
        'images' => [],
        'merchant' => [
          'id' => $product->merchant()->ID,
          'name' => $product->merchant()->Name,
          'category' => $product->merchant()->category()->Name
        ]
      ]);

      foreach ($product->images() as $image) {
        array_push($resource[$key]['images'], $image->absoluteLink());
      }
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success get all products',
      'products' => $resource
    ]));
  }

  public function addProduct(HTTPRequest $request, Merchant $merchant)
  {
    // cek apakah ada data yang dikirimkan 
    $title = $request->postVar('title');
    $price = $request->postVar('price');
    $image = $request->postVar('image');

    if (is_null($title) || is_null($price) || is_null($image)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 401,
      'message' => 'Parameter required',
    ]));

    // cek apakah yang diupload itu gambar 
    $type = explode('/', $image['type']);

    if ($type[0] !== 'image') {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'provided an image',
      ]));
    }

    // buat instance product 
    $product = Product::create();
    $product->Title = $title;
    $product->Price = $price;
    $product->MerchantID = $merchant->ID;
    $product->isAvailable = false;

    try {
      // tulis data 
      $product_id = $product->write();
      // upload gambar 
      $this->_uploadImage($image, $product_id);
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage(),
      ]));
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'add product successfully',
    ]));
  }

  public function _uploadImage($tmp_file, $product_id)
  {
    $image = CustomImage::create();
    $image->ProductID = $product_id;

    $upload = new Upload();
    $upload->getValidator()->setAllowedExtensions(['jpg', 'jpeg', 'png']);
    $upload->loadIntoFile($tmp_file, $image, 'product/' . $product_id);

    if ($upload->isError()) throw new Exception($upload->getErrors()[0]);

    return true;
  }
}
