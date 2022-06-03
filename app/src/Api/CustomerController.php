<?php

namespace Api;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Api\Api;
use Api\Customer;
use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use Token;

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
      if ($request->isPOST()) return $this->login($request);
    }

    // cek apakah param berisi logout
    if ($request->param('param') === 'logout') {
      if ($request->isGET()) return $this->logout($request);
    }

    // cek apakah param berisi forget-password
    if ($request->param('param') === 'forget-password') {
      if ($request->isGET()) return $this->forget_password($request);
    }
  }

  public function logout(HTTPRequest $request)
  {
    // cek apakah ada access token di header
    if (is_null($jwt = $request->getHeader('access-token'))) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 401,
      'message' => "Unauthorized",
    ]));

    try {
      // ambil informasi yang ada diheader 
      $decoded = JWT::decode($jwt, new Key(Environment::getEnv('SECRET_KEY'), Environment::getEnv('ALG')));
    } catch (Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 403,
        'message' => $e->getMessage(),
      ]));
    }

    // cek apakah user masih login 
    $isLogin = Token::get()->filter('MemberID', $decoded->id)->first();
    if (is_null($isLogin)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => "User not login",
    ]));

    // jika belum login 
    // hapus token 
    $isLogin->delete();
    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => "user logged out successfully",
    ]));
  }

  public function login(HTTPRequest $request)
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
    $customers = Customer::get()->filter('Email', $email)->first();
    if (is_null($customers)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => "User Not Found",
    ]));

    // cek password 
    $auth = new MemberAuthenticator;
    $result = $auth->checkPassword($customers, $password);

    if (!$result->isValid()) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 403,
      'message' => $result->getMessages()[0]['message']
    ]));

    // cek apakah memberid sudah tersedia, cegat 
    $token = Token::get()->filter('MemberID', $customers->ID)->first();
    if (!is_null($token)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'user is still logged in'
    ]));

    // jika password benar buat token 
    $payload = [
      'id' => $customers->ID,
      'email' => $customers->Email
    ];

    $jwt = JWT::encode($payload, Environment::getEnv('SECRET_KEY'), Environment::getEnv('ALG'));

    // lalu tambahkan token tersebut ke database 
    $token = Token::create();
    $token->MemberID = $customers->ID;
    $token->Token = $jwt;

    try {
      $token->write();
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
      'message' => 'login success',
      'access_token' => $jwt
    ]));
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
    $customer = Customer::get()->filter('Email', $email)->first();

    // jika ada
    if (!is_null($customer)) {
      //  cek apakah user sudah divalidate
      // jika sudah  
      if ($customer->isValidated === 1) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 409,
        'message' => "User Already exists",
      ]));

      // kalau belum 
      $customer->delete();
    }

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
