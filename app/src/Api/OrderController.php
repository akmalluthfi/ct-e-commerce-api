<?php

namespace Api;

use Api\Customer;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SilverStripe\Core\Environment;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

class OrderController extends Controller
{
  public function init()
  {
    parent::init();
    $this->getResponse()->addHeader("Content-type", "application/json");
    $this->getResponse()->addHeader(
      'Access-Control-Allow-Origin',
      'http://localhost:3000'
    );
    $this->getResponse()->addHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, x-api-key, access-token');
    $this->getResponse()->addHeader('Access-Control-Allow-Methods', 'GET, PUT, DELETE, POST');

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
    // cek apakah dibody terdapat response
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

    // cek apakah method yang digunakan adalah put 
    if ($request->isPUT()) {
      // jika valid, cek apakah itu milik customer 
      $merchant = Merchant::get_by_id($decoded->id);

      if (is_null($merchant)) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 403,
        'message' => 'Forbidden',
      ]));

      // cek apakah ada id yang dikirimkan 
      if (
        is_numeric($request->param('id')) &&
        $request->param('resource') === 'status'
      ) return $this->confirm_order($request, $merchant);
    }

    // jika valid, cek apakah itu milik customer 
    $customer = Customer::get_by_id($decoded->id);

    if (is_null($customer)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 403,
      'message' => 'Forbidden',
    ]));

    if ($request->isPOST()) return $this->create_order($customer);

    return $this->httpError(404);
  }

  public function confirm_order(HTTPRequest $request, Merchant $merchant)
  {
    $order = $merchant->orders()->byID($request->param('id'));

    // cek apakah ada 
    if (is_null($order)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'Order not found',
    ]));

    // ambil data yang dikirimkan merchant
    $data = json_decode($request->getBody());

    $info = [
      'merchant' => $order->merchant()->FirstName,
      'customer' => $order->customer()->name(),
      'total' => $order->Total,
      'order_details' => []
    ];

    foreach ($order->orderDetails() as $order_detail) {
      array_push($info['order_details'], [
        'product_name' => $order_detail->product()->Title,
        'price' => $order_detail->product()->Price,
        'quantity' => $order_detail->Quantity,
        'sub_total' => $order_detail->SubTotal,
      ]);
    }

    if ($data->status === 2) {
      // berarti order diterima
      $order->Status = $data->status;
      // $order->write();
      // kirimkan email ke customer 
      EmailHelper::sendOrderAccepted($order->customer()->Email, $info);

      return $this->getResponse()->setBody(json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'Order rejected successfully',
      ]));
    } else if ($data->status === 3) {
      // berarti order ditolak
      $order->Status = $data->status;
      // $order->write();
      // kirimkan email ke customer 
      EmailHelper::sendOrderRejected($order->customer()->Email, $info);

      return $this->getResponse()->setBody(json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'Order Accepted',
      ]));
    }
  }

  public function create_order(Customer $customer)
  {
    // ambil product yang dicheck di carts user
    $carts = $customer->carts()->filter([
      'isChecked' => true,
      'Product.isAvailable' => true
    ]);

    // ambil merchant id dari product pertama 
    $merchant_id = $carts->first()->product()->MerchantID;

    // cek apakah semua product cart dari merchant yang sama,
    $diff_merchant_id = $carts->filter('Product.MerchantID:not', $merchant_id);

    // jika ada barang dari merchant yang berbeda 
    if ($diff_merchant_id->exists()) {
      // return warning: tidak boleh checkout dari merchant yang berbeda
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'Bad Request: Cannot checkout products from different merchant',
      ]));
    }

    // jika sudah berhasil melewati proses diatas berarti cart sekarang sudah aman 
    // hitung sub total dari tiap total 

    $product_list = [];
    foreach ($carts as $cart) {
      array_push($product_list, [
        'product_id' => $cart->ProductID,
        'quantity' => $cart->Quantity,
        'merchant_email' => $cart->product()->merchant()->Email,
        'product_name' => $cart->product()->Title,
        'price' => $cart->product()->Price,
        'sub_total' => $cart->Quantity * $cart->product()->Price
      ]);
    }

    $total = array_reduce($product_list, function ($curr, $item) {
      $curr += $item['sub_total'];
      return $curr;
    });

    $order = Order::create();
    $order->Total = $total;
    $order->CustomerID = $customer->ID;
    $order->MerchantID = $merchant_id;
    $order->Status = 0;

    try {
      $order_id = $order->write();
      // $order_id = 0;

      // tambahkan tiap tiap product ke order list 
      foreach ($product_list as $product) {
        $order_detail = OrderDetail::create();

        $order_detail->SubTotal = $product['sub_total'];
        $order_detail->Quantity = $product['quantity'];
        $order_detail->ProductID = $product['product_id'];
        $order_detail->OrderID = $order_id;

        $order_detail->write();
      }

      // kirim email confirmation
      EmailHelper::sendOrderConfirmation($product_list[0]['merchant_email'], [
        'product' => $product_list,
        'total' => $total,
      ]);

      // jika berhasil delete product didalam cart
      foreach ($carts as $cart) {
        $cart->delete();
      }

      return $this->getResponse()->setBody(json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'Order created successfully',
      ]));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'Something went wrong : ' . $e->getMessage(),
      ]));
    }
  }
}
