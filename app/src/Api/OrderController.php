<?php

namespace Api;

use Api\Customer;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SilverStripe\Core\Environment;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;

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

    $member = Member::get_by_id($decoded->id);

    if ($member->ClassName === Merchant::class) return $this->handleMerchant($request, $member);

    if ($member->ClassName === Customer::class) return $this->handleCustomer($request, $member);

    return $this->httpError(404);
  }

  public function handleMerchant(HTTPRequest $request, Merchant $merchant)
  {
    // cek verb apa yang dipakai 
    // jika put maka ubah order status 
    // jika get maka ambil semua order milik merchant 
    // jika get dengan id maka ambil order detail milik merchant 
    var_dump('merchant');
    die();
  }

  public function getSingleOrder($order_id, $member)
  {
    $member_role = $member->className === Customer::class ? 'customer' : 'merchant';

    var_dump('get order detail #' . $order_id . ' from ' . $member_role . ': ' . $member->ID);
    die();
  }

  public function getOrders($member)
  {
    $orders = $member->orders();

    $orders_list = [];
    foreach ($orders as $order) {
      array_push($orders_list, [
        'id' => $order->ID,
        'created' => $order->Created,
        'total' => $order->Total,
        'status' => $order->Status,
        'merchant' => [
          'name' => $order->merchant()->Name,
          'id' => $order->MerchantID
        ]
      ]);
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'Success get Orders',
      'orders' => $orders_list
    ]));
  }

  public function create_order($data, $customer)
  {
    // get ordered product
    $products =  Product::get()->filter('ID', array_column($data, 'id'));

    // get merchant from first product 
    $merchant = $products[0]->merchant()->toMap();

    // check if product has same merchant
    $diff_merchant_id = $products->filter('MerchantID:not', $merchant['ID']);
    if ($diff_merchant_id->exists()) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'Bad Request: Cannot checkout products from different merchant',
    ]));

    // change object to array
    $ordered_products = $products->toNestedArray();

    // calculate total product
    $total = 0;
    foreach ($ordered_products as $key => $product) {
      $subTotal = $product['Price'] * $data[$key]->quantity;
      $total += $subTotal;
      // add new key 
      $ordered_products[$key]['quantity'] = $data[$key]->quantity;
      $ordered_products[$key]['subTotal'] = $subTotal;
      // remove unnecessary key
      unset($ordered_products[$key]['MerchantID']);
      unset($ordered_products[$key]['ClassName']);
      unset($ordered_products[$key]['RecordClassName']);
      unset($ordered_products[$key]['Created']);
      unset($ordered_products[$key]['LastEdited']);
    }

    // create order
    $order = Order::create();
    $order->Total = $total;
    $order->CustomerID = $customer->ID;
    $order->MerchantID = $merchant['ID'];
    $order->Status = 0;

    try {
      $order_id = $order->write();

      // create order details
      foreach ($ordered_products as $product) {
        $order_detail = OrderDetail::create();

        $order_detail->SubTotal = $product['subTotal'];
        $order_detail->Quantity = $product['quantity'];
        $order_detail->ProductID = $product['ID'];
        $order_detail->OrderID = 0;
        $order_detail->OrderID = $order_id;

        $order_detail->write();
      }

      // delete product in cart
      foreach ($customer->carts()->filter('ProductID', array_column($data, 'id')) as $product_cart) {
        $product_cart->delete();
      }

      // send email confirmation
      EmailHelper::sendOrderConfirmation($merchant['Email'], [
        'product' => $ordered_products,
        'total' => $total,
        'merchant' => $merchant
      ]);

      // return success
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

  public function handleCustomer(HTTPRequest $request, Customer $customer)
  {
    if ($request->isPOST()) return $this->create_order(json_decode($request->getBody()), $customer);

    if ($request->isGET() && $request->param('id')) return $this->getSingleOrder($request->param('id'), $customer);

    if ($request->isGET()) return $this->getOrders($customer);



    // cek verb 
    // jika get tanpa id 
    // maka ambil semua order milik customer 
    // jika get dengan id 
    // maka ambil order detail milik customer 
    // jika post maka create order 
    var_dump('customer');
    die();
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
}
