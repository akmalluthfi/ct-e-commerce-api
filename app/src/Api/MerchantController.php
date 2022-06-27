<?php

namespace Api;

use Token;
use Api\Api;
use Api\Merchant;
use Api\EmailHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Api\TokenController;
use Exception;
use SilverStripe\Security\Member;
use SilverStripe\Core\Environment;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;

class MerchantController extends Controller
{
  public function init()
  {
    parent::init();
    $this->getResponse()->addHeader("Content-type", "application/json");
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
    if (!is_null($this->response->getBody())) return $this->response;

    if ($request->isGET()) {
      // cek apakah ada access token 
      $accTk = $request->getHeader('access-token');

      if (is_null($accTk)) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 401,
        'message' => 'Unauthorized',
      ]));

      // cek apakah valid
      try {
        $decoded = JWT::decode($accTk, new Key(Environment::getEnv('SECRET_KEY'), 'HS256'));
      } catch (\Exception $e) {
        return $this->getResponse()->setBody(json_encode([
          'success' => false,
          'code' => 401,
          'message' => 'Unauthorized',
        ]));
      }

      // ambil param
      $id = $request->param('action');
      $field = $request->param('field');

      // jika tidak ada param 
      if (is_null($id) && is_null($field)) return $this->getAllMerchants($request);

      // cek param, apakah int
      if (is_numeric($id)) {
        // cek apakah ada field yang dikirimkan 
        // get merchants by id 
        if (is_null($field = $request->param('field'))) return $this->getMerchantById($id);

        if ($field === 'products') return $this->getMerchantProducts($id);
      }
    }

    if ($request->isPOST()) {
      $action = $request->param('action');

      if ($action === 'register') return $this->register($request);
      if ($action === 'login') return $this->login($request);
      if ($action === 'logout') return $this->logout($request);
      if ($action === 'forget-password') return $this->forget_password($request);
      if ($action === 'change-password') return $this->change_password($request);
    }

    return $this->httpError(404);
  }

  public function getAllMerchants(HTTPRequest $request)
  {
    // $merchants = Merchant::get();
    $merchants = Merchant::get()->filter([
      'isValidated' =>  true,
      'isApproved' => true,
    ]);

    $filter = [];

    if (!is_null($keyword = $request->getVar('s'))) {
      $filter['Products.Title:PartialMatch'] = $keyword;
    }

    // tambahkan filter
    $merchants = $merchants->filter($filter);
    // array untuk menampung hasil 
    $resource = [];
    foreach ($merchants as $merchant) {
      array_push($resource, [
        'id' => $merchant->ID,
        'name' => $merchant->FirstName,
        'email' => $merchant->Email,
        'is_open' => $merchant->isOpen,
        'category' => $merchant->Category()->Name,
        'picture' => $merchant->Picture()->AbsoluteLink(),
      ]);
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'Success get merchant',
      'merchants' => $resource
    ]));
  }

  public function getMerchantProducts($id)
  {
    $products = Product::get()->filter('MerchantID', $id);
    $resource = [];

    foreach ($products as $key => $product) {
      array_push($resource, [
        'id' => $product->ID,
        'title' => $product->Title,
        'price' => $product->Price,
        'isAvailable' => $product->isAvailable,
        'images' => [],
      ]);

      foreach ($product->images() as $image) {
        array_push($resource[$key]['images'], $image->AbsoluteLink());
      }
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success get product with merchant',
      'product' => $resource
    ]));
  }

  public function getMerchantById($id)
  {
    $merchant = Merchant::get()->filter([
      'ID' => $id,
      'isApproved' => true,
      'isValidated' => true
    ])->first();

    // jika tidak ada 
    if (is_null($merchant)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'Merchant not found',
    ]));

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'Success get merchant',
      'data' => [
        'id' => $merchant->ID,
        'name' => $merchant->FirstName,
        'email' => $merchant->Email,
        'is_open' => $merchant->isOpen,
        'category' => $merchant->Category()->Name,
        'picture' => $merchant->Picture()->AbsoluteLink(),
      ]
    ]));
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
    $merchant = Merchant::get()->filter('Email', $email)->first();
    if (is_null($merchant)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => "Email not found",
    ]));

    // cek apakah merchant ini valid 
    if ($merchant->isValidated === 0) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => "request cannot be accepted, user not valid",
    ]));

    // create and store verify token
    $current_date = strtotime(date('Y-m-d H:i:s'));
    $expired = date('Y-m-d H:i:s', strtotime('+3 hours', $current_date));
    $token = VerifyController::createVerifyToken($expired, $merchant->ID);

    $result = EmailHelper::sendEmailForgotPassword($merchant->Email, $token);

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
    // jika belum login 
    if (is_null($isLogin)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => "User not login",
    ]));

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

    // cek apakah sudah di approve 
    if ($member->isApproved === 0) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 403,
      'message' => 'Approvement required',
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
    $member = Member::get()->filter('Email', $email)->first();

    // jika ada
    if (!is_null($member)) {
      //  cek apakah user sudah divalidate
      // jika sudah  
      if ($member->isValidated === 1) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 409,
        'message' => "Merchants Already exists",
      ]));

      // kalau belum 
      $member->delete();
    }

    // jika sudah tambahkan ke database
    $merchant = Merchant::create();
    $merchant->Email = $email;
    $merchant->Password = $password;
    $merchant->IsValidated = false;
    $merchant->PictureID = 3;

    try {
      $merchant->write();
      // Buat token untuk verifikasi email
      $token = TokenController::createToken($merchant->Created, $merchant->ID);
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

    // kirimkan email ke admin
    $emailAdmin = EmailHelper::sendEmailApproved();
    if ($emailAdmin !== true) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 500,
        'message' => 'email approved by admin, failed to send'
      ]));
    }

    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'success create new merchant, please verifiction first before login, wait at least 2 days to approved'
    ]));
  }
}
