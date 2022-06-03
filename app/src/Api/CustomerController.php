<?php

namespace Api;

use Api\Api;
use Api\Customer;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationException;

class CustomerController extends Controller
{
  public function init()
  {
    parent::init();

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

    $this->getResponse()->addHeader("Content-type", "application/json");
  }

  public function index(HTTPRequest $request)
  {
    // cek apakah param berisi register
    if ($request->param('param') === 'register') {
      // cek apakah dia lewat post 
      if ($request->isPOST()) return $this->register($request);
    }

    // cek apakah param berisi login
    if ($request->param('param') === 'login') {
      var_dump('user login');
      return;
    }
  }

  public function register(HTTPRequest $request)
  {
    // ambil param body 
    $email = $request->postVar('email');
    $password = $request->postVar('password');

    // cek apakah param ada 
    if (is_null($email) || is_null($password)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => "request cannot be accepted, parameter required",
    ]));

    // cek apakah email sudah tersedia di database 
    $customers = Customer::get()->filter('Email', $email);

    // jika ada jangan diperbolehkan 
    if ($customers->exists()) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 409,
      'message' => "User Already exists",
    ]));

    // jika sudah tambahkan ke database
    $newCustomer = Customer::create();
    $newCustomer->Email = $email;
    $newCustomer->Password = $password;
    $newCustomer->isValidated = false;

    try {
      $newCustomer->write();
    } catch (ValidationException $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage()
      ]));
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success create new customer'
    ]));

    // ! Kurang kirim email 

    // jika berhasil menambahkan user 
    // kirim email verifiy 
  }
}
