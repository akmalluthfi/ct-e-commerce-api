<?php

namespace Api;

use Api\Api;
use Api\Verify;
use Api\Customer;
use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationException;

class MemberController extends Controller
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
    if (!$request->isPOST()) return $this->httpError(404);
  }

  public function register(HTTPRequest $request)
  {
    // ambil param body 
    $role = $request->postVar('role');
    $email = $request->postVar('email');
    $password = $request->postVar('password');

    // cek apakah param ada 
    if (is_null($email) || is_null($password) || is_null($role)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => "request cannot be accepted, parameter required",
    ]));

    // cek role apa yang dipakai 
    if ($role === 'customers') return $this->_addCustomers($email, $password);
    if ($role === 'merchants') return $this->_addMerchants($email, $password);

    // selain diatas 
    return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'Enter a valid role'
    ]));
  }

  public function _addMerchants($email, $password)
  {
    var_dump('Belum dibuat ');
    die();
  }

  public function _createToken($created, $id)
  {
  }

  public function _sendEmailValidation($to, $token)
  {
    // kirim email 
    $email = Email::create();
    $email->setHTMLTemplate('Email\\verify');
    $email->setData([
      'link' => 'http://localhost:8080' . BASE_URL . "/api/verify/$token"
    ]);
    $email->setFrom('no-reply@admin.com', 'noreply');
    $email->setTo($to);
    $email->setSubject('Verify email address for e-commerce');

    return $email->send();
  }

  public function _addCustomers($email, $password)
  {
    // cek apakah email sudah tersedia di database 
    $customer = Customer::get()->filter('Email', $email)->first();
    // jika ada
    if (!is_null($customer)) {
      //  cek apakah user sudah divalidate
      // jika sudah  
      if ($customer->isValidated === 1) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => "Customers Already exists",
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
      // lalu create token 
      $token = $this->_createToken($newCustomer->Created, $newCustomer->ID);

      // kirim email 
      $result = $this->_sendEmailValidation($email, $token);

      if ($result !== true) {
        throw new Exception('Email gagal dikirimkan');
      }
    } catch (Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 500,
        'message' => $e->getMessage()
      ]));
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'Success create customers, we send a email'
    ]));
  }

  public function login(HTTPRequest $request)
  {
    var_dump('ini login');
    var_dump($request);
    die();
  }

  public function logout()
  {
    var_dump('ini');
    die();
  }

  public function forgot_password()
  {
    var_dump('ini forgot');
    die();
  }
}
