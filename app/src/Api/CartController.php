<?php

namespace Api;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;

class CartController extends Controller
{
  public function init()
  {
    parent::init();
    $this->getResponse()->addHeader("Content-type", "application/json");

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
    // cek apakah sudah ada response body 
    if (!is_null($this->response->getBody())) return $this->response;

    $access_token = $request->getHeader('access-token');
    // cek apakah ada access token
    if (is_null($access_token)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 401,
      'message' => "Unauthorized",
    ]));
    // jika ada cek apakah valid
    try {
      $decoded = JWT::decode($access_token, new Key(Environment::getEnv('SECRET_KEY'), 'HS256'));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'Unauthorized',
      ]));
    }
    // jika valid, cek apakah itu milik customer 
    $customer = Customer::get_by_id($decoded->id);

    if (is_null($customer)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 403,
      'message' => 'Forbidden',
    ]));

    // jika iya, method yang dipakai
    if ($request->isGET()) return $this->get_cart($customer);
    if ($request->isPOST()) return $this->add_to_cart($request, $customer);

    if ($request->isPUT()) {
      $id = $request->param('id');
      $resource = $request->param('resource');

      if (is_null($id) && is_null($resource)) return $this->edit_status_cart($request, $customer);

      if (is_numeric($id) && $resource === 'quantity') return $this->edit_quantity_cart($request, $customer);
    }

    // delete product in cart
    if ($request->isDELETE()) {
      $id = $request->param('id');

      if (is_numeric($id)) return $this->delete_from_cart($customer, $id);
    }

    return $this->httpError(404);
  }

  public function edit_status_cart(HTTPRequest $request, Customer $customer)
  {
    // ambil data yang dikirim lewat json
    $products = json_decode($request->getBody());
    // cari product di cart yang id nya sama dengan product id yang dikirimkan 
    // jika ada ubah, jika tidak ada biarkan
    foreach ($products as $product) {
      $cart = $customer->carts()->filter('ProductID', $product->product_id)->first();
      if (!is_null($cart)) {
        $cart->isChecked = $product->is_checked;
        $cart->write();
      }
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'Success change status product',
    ]));
  }

  public function delete_from_cart(Customer $customer, $product_id,)
  {
    // cari product didalam customer carts
    $carts = $customer->carts()->filter('ProductID', $product_id)->first();

    if (is_null($carts)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'Product not found',
    ]));
    // jika ada, hapus
    $carts->delete();

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'Success delete product in carts',
    ]));
  }

  public function edit_quantity_cart(HTTPRequest $request, Customer $customer)
  {
    // cek apakah parameter dikirim
    $body = json_decode($request->getBody());

    if (is_null($body)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'parameter required',
    ]));

    // cek apakah quantity int 
    if (!is_numeric($body->quantity)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'parameter must int or string int',
    ]));

    // ambil product
    $product = $customer->carts()->filter('ProductID', $request->param('id'))->first();
    // cek apakah product ditemukan
    if (is_null($product)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'product in cart not found',
    ]));

    $product->Quantity = $body->quantity;
    try {
      $product->write();
      return $this->getResponse()->setBody(json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'Success edit quantity product',
      ]));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage(),
      ]));
    }
  }

  public function get_cart(Customer $customer)
  {
    $resource = [
      'customer' => [
        'id' => $customer->ID,
        'email' => $customer->Email,
        'first_name' => $customer->FirstName,
        'surname' => $customer->Surname
      ],
      'products' => []
    ];

    foreach ($customer->carts() as $cart) {
      array_push($resource['products'], [
        'product_id' => $cart->ProductID,
        'quantity' => $cart->Quantity,
        'is_checked' => $cart->isChecked,
        'is_available' => $cart->product()->isAvailable,
        'product_name' => $cart->product()->Title,
        'product_price' => $cart->product()->Price,
        'merchant' => [
          'name' => $cart->product()->merchant()->FirstName,
          'picture' => $cart->product()->merchant()->Picture()->AbsoluteLink(),
        ]
      ]);
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'Success get all products in customer cart',
      'data' => $resource
    ]));
  }

  public function add_to_cart(HTTPRequest $request, Customer $customer)
  {
    // cek apakah ada data yang dikirimkan 
    $product_id = $request->postVar('product_id');
    $quantity = $request->postVar('quantity');

    if (!is_numeric($product_id) || !is_numeric($quantity)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'Parameter required, parameter must int',
    ]));

    // cek apakah product id tersebut tersedia 
    $product = Product::get_by_id($product_id);

    if (is_null($product)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'Product not found',
    ]));

    if ($product->isAvailable === 0) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 403,
      'message' => 'product unavailable',
    ]));

    // cek apakah didalam cart sebelumnya sudah tersedia product dengan id tersebut 
    // jika sudah tambah ke quantity 
    $cart = $customer->carts()->filter('ProductID', $product_id)->first();
    if (!is_null($cart)) {
      $cart->Quantity += intval($quantity);

      try {
        $cart->write();
        return $this->getResponse()->setBody(json_encode([
          'success' => true,
          'code' => 200,
          'message' => 'success add product to carts',
        ]));
      } catch (\Exception $e) {
        return $this->getResponse()->setBody(json_encode([
          'success' => false,
          'code' => 400,
          'message' => $e->getMessage(),
        ]));
      }
    }

    $cart = Cart::create();
    $cart->Quantity = $quantity;
    $cart->isChecked = false;
    $cart->ProductID = $product_id;
    $cart->CustomerID = $customer->ID;

    try {
      $cart->write();
      return $this->getResponse()->setBody(json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'success add product to carts',
      ]));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage(),
      ]));
    }
  }
}
