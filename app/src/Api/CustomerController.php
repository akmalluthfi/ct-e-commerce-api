<?php

namespace Api;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Api\Api;
use Api\Customer;
use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use Token;

class CustomerController extends Controller
{
  public function init()
  {
    parent::init();
    $this->getResponse()->addHeader("Content-Type", "application/json");
    $this->getResponse()->addHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
    $this->getResponse()->addHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, x-api-key');

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

    if (!$request->isPOST()) return $this->httpError(404);
    $action = $request->param('action');

    if ($action === 'register') return $this->register($request);
    if ($action === 'login') return $this->login($request);
    if ($action === 'logout') return $this->logout($request);
    if ($action === 'forget-password') return $this->forget_password($request);
    if ($action === 'change-password') return $this->change_password($request);

    return $this->httpError(404);
  }

  public function change_password(HTTPRequest $request)
  {
    // cek apakah password === confirmation password
    $body = json_decode($request->getBody());

    // cek apakah ada token yang terkirim 
    $token = Verify::get()->filter('Token', $body->token)->first();

    if (is_null($token)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => "invalid token",
    ]));

    $member = Member::get_by_id($token->MemberID);

    $member->Password = $body->password;

    try {
      $member->write();
    } catch (ValidationException $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage(),
      ]));
    }

    // hapus token 
    $token->delete();

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success change password',
    ]));
  }

  public function forget_password(HTTPRequest $request)
  {
    // apakah ada email yang dikirim 
    if (is_null($email = $request->postVar('email'))) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => "request cannot be accepted, parameter required",
    ]));
    // ambil email yang dikirim
    // cek apakah email ada didatabase 
    $customer = Customer::get()->filter('Email', $email)->first();
    if (is_null($customer)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => "Email not found",
    ]));

    // cek apakah customer ini valid 
    if ($customer->isValidated === 0) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => "request cannot be accepted, user not valid",
    ]));

    // create and store verify token
    $current_date = strtotime(date('Y-m-d H:i:s'));
    $expired = date('Y-m-d H:i:s', strtotime('+3 hours', $current_date));
    $token = VerifyController::createVerifyToken($expired, $customer->ID);

    $result = EmailHelper::sendEmailForgotPassword($customer->Email, $token);

    if ($result !== true) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 500,
        'message' => 'Something went wrong'
      ]));
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success send email verification'
    ]));
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
    $body = json_decode($request->getBody());

    $email = $body->email;
    $password = $body->password;

    // cek apakah param ada 
    if (is_null($email) || is_null($password)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => "request cannot be accepted, parameter required",
    ]));

    // cek apakah email sudah tersedia di database 
    $member = Member::get()->filter('Email', $email)->first();
    if (is_null($member)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => "User Not Found",
    ]));

    // cek apakah sudah divalidasi 
    if ($member->isValidated === 0) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 403,
      'message' => "Validation required",
    ]));

    // cek password 
    $auth = new MemberAuthenticator;
    $result = $auth->checkPassword($member, $password);

    if (!$result->isValid()) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 403,
      'message' => $result->getMessages()[0]['message']
    ]));

    // cek apakah member id sudah tersedia, cegat 
    $token = Token::get()->filter('MemberID', $member->ID)->first();
    if (!is_null($token)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'user is still logged in'
    ]));

    // jika password benar buat token 
    $payload = [
      'id' => $member->ID,
      'email' => $member->Email
    ];

    $jwt = JWT::encode($payload, Environment::getEnv('SECRET_KEY'), Environment::getEnv('ALG'));

    // lalu tambahkan token tersebut ke database 
    $token = Token::create();
    $token->MemberID = $member->ID;
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
    $body = json_decode($request->getBody(), true);

    // cek apakah param ada 
    if (!isset($body['email']) || !isset($body['password'])) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => "request cannot be accepted, parameter required",
    ]));

    $email = $body['email'];
    $password = $body['password'];

    // cek apakah email sudah tersedia di database 
    $member = Member::get()->filter('Email', $email)->first();

    // jika ada
    if (!is_null($member)) {
      //  cek apakah user sudah divalidate
      // jika sudah  
      if ($member->isValidated === 1) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 409,
        'message' => 'Email already registered',
      ]));

      // kalau belum 
      $member->delete();
    }

    // cek apakah email valid 
    $pattern = "/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/";
    if (!preg_match($pattern, $email)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'Email Invalid',
    ]));

    // jika sudah tambahkan ke database
    $customer = Customer::create();
    $customer->Email = $email;
    $customer->Password = $password;
    $customer->isValidated = false;
    $customer->PictureID = 3;

    try {
      $customer->write();
      // Buat token
      $token = TokenController::createToken($customer->Created, $customer->ID);
    } catch (ValidationException $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage()
      ]));
    }

    $result = EmailHelper::sendEmailValidation($email, $token);

    if ($result !== true) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 500,
        'message' => 'Something went wrong'
      ]));
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success create new customer, we send a email verification, please check'
    ]));
  }
}
