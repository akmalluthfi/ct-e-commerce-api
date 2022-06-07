<?php

namespace Api;

use Api\Api;
use Api\Customer;
use Api\Merchant;
use Api\EmailHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Api\VerifyController;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Upload;
use SilverStripe\Security\Member;
use SilverStripe\Core\Environment;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

class ProfileMerchantController extends Controller
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
    if (!is_null($this->response->getBody())) return $this->response;

    // cek apakah ada ada header access token 
    $access_token = $request->getHeader('access-token');
    if (is_null($access_token)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 401,
      'message' => "Unauthorized",
    ]));

    try {
      $decoded = JWT::decode($access_token, new Key(Environment::getEnv('SECRET_KEY'), 'HS256'));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 404,
        'message' => $e->getMessage(),
      ]));
    }

    // cek apakah ada customer dengan id tersebut 
    $merchant = Merchant::get_by_id($decoded->id);

    if (is_null($merchant)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'Merchant not found',
    ]));

    // cek methodnya 
    if ($request->isPUT()) {
      // cek apakah action yang dikirim adalah status
      if ($request->param('action') === 'status') return $this->setStatus($merchant, json_decode($request->getBody()));

      return $this->editProfile($merchant, json_decode($request->getBody()));
    }

    if ($request->isPOST()) {
      // cek apakah action adalah picture
      if ($request->param('action') === 'picture') return $this->editPicture($merchant, $request->postVar('picture'));
    }

    return $this->httpError(404);
  }

  public function editPicture(Merchant $merchant, $picture)
  {
    // cek apakah ada picture 
    if (is_null($picture)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'Picture required'
    ]));

    try {
      $image = Image::create();
      $upload = new Upload();
      $upload->loadIntoFile($picture, $image, 'merchant_profile/' . $merchant->ID);
      // buat validasi 
      $upload->getValidator()->setAllowedExtensions(['jpg', 'jpeg', 'png']);

      // jika berhasil diupload 
      $merchant->PictureID = $image->ID;

      $merchant->write();
      return $this->getResponse()->setBody(json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'Success change profile picture'
      ]));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage()
      ]));
    }
  }

  public function setStatus(Merchant $merchant, $data)
  {
    // cek apakah ada data status
    if (is_null($data)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'Param required',
    ]));
    $merchant->isOpen = $data->status;

    try {
      $merchant->write();

      return $this->getResponse()->setBody(json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'success change status merchant',
      ]));
    } catch (\Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage(),
      ]));
    }
  }

  public function editProfile($merchant, $data)
  {
    $allowed_time = strtotime('+1 hours', strtotime($merchant->LastEdited));
    // $plus_one_hour = date('Y-m-d H:i:s', strtotime('+1 hours', $created));
    // cari waktu 1 jam dari merchant creates
    $current_date = strtotime(date('Y-m-d H:i:s'));
    // cek kapan terakhir kali data diubah 
    // jika kurang dari 1 jam maka, gagalkan 
    // ! Hidupkan jika mau 
    if ($allowed_time > $current_date) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 400,
      'message' => 'Wait after 1 hours',
    ]));
    // jika lebih dari 1 jam 
    // ubah data
    $merchant->FirstName = $data->name;
    $merchant->CategoryID = $data->category;
    // cek apakah email berubah
    if ($merchant->Email !== $data->email) {
      // email berubah 
      // cek apakah email yang beru terdapat didatabase
      $member = Member::get()->filter('Email', $data->email);
      if ($member->exists()) return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => 'Email already exists',
      ]));

      // kirimkan email untuk konfirmasi
      // buat token verify 
      // set mau 3 jam dari sekarang
      $expired = date('Y-m-d H:i:s', strtotime('+3 hours', $current_date));
      // create token cara lama 
      $token = VerifyController::createJWT([
        'id' => $merchant->ID,
        'email' => $data->email,
      ], $expired, $merchant->ID);

      // kirimkan ke email 
      $result = EmailHelper::sendEmailChangeEmail($data->email, $token);
      // cek apakah result 
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
        'message' => 'success send email verification',
      ]));
    }

    // perbarui data 
    try {
      $merchant->write();
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
      'message' => 'profile changed successfully',
    ]));
  }
}
