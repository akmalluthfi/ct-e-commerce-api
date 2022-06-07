<?php

namespace Api;

use Exception;
use Api\Customer;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SilverStripe\Assets\Upload;
use SilverStripe\Core\Environment;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Assets\Image;

class ProfileCustomerController extends Controller
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
    // cek apakah ada body dari response 
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
    } catch (Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 404,
        'message' => $e->getMessage(),
      ]));
    }

    // cek apakah ada customer dengan id tersebut 
    $customer = Customer::get_by_id($decoded->id);

    if (is_null($customer)) return $this->getResponse()->setBody(json_encode([
      'success' => false,
      'code' => 404,
      'message' => 'Customer not found',
    ]));

    // cek method apa yang dipakai 
    if ($request->isGET()) return $this->getCustomer($customer);

    if ($request->isPOST()) {
      if ($request->param('action') === 'picture') return $this->editPicture($customer, $request->postVar('picture'));
    }

    if ($request->isPUT()) {
      // jika tidak ada
      return $this->editProfile($customer, json_decode($request->getBody()));
    }

    return $this->httpError(404);
  }

  public function editPicture(Customer $customer, $picture)
  {
    try {
      $image = Image::create();
      $upload = new Upload();
      $upload->loadIntoFile($picture, $image, 'user_profile/' . $customer->ID);
      // buat validasi 
      $upload->getValidator()->setAllowedExtensions(['jpg', 'jpeg', 'png']);

      // jika berhasil diupload 
      $customer->PictureID = $image->ID;

      $customer->write();
      return $this->getResponse()->setBody(json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'Success change profile picture'
      ]));
    } catch (Exception $e) {
      return $this->getResponse()->setBody(json_encode([
        'success' => false,
        'code' => 400,
        'message' => $e->getMessage()
      ]));
    }
  }

  public function editProfile(Customer $customer, $data)
  {
    $allowed_time = strtotime('+1 hours', strtotime($customer->LastEdited));
    // $plus_one_hour = date('Y-m-d H:i:s', strtotime('+1 hours', $created));
    // cari waktu 1 jam dari customer creates
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
    // ubah first_name dan surname 
    $customer->FirstName = $data->first_name;
    $customer->Surname = $data->surname;
    // cek apakah email berubah
    if ($customer->Email !== $data->email) {
      // email berubah 
      // kirimkan email untuk konfirmasi
      // buat token verify 
      // set mau 3 jam dari sekarang
      $expired = date('Y-m-d H:i:s', strtotime('+3 hours', $current_date));
      // create token cara lama 
      $token = VerifyController::createJWT([
        'id' => $customer->ID,
        'email' => $data->email,
      ], $expired, $customer->ID);

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
      $customer->write();
    } catch (Exception $e) {
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

  public function getCustomer(Customer $customer)
  {
    return $this->getResponse()->setBody(json_encode([
      'success' => true,
      'code' => 200,
      'message' => 'Success get customer with id: ' . $customer->ID,
      'data' => [
        'id' => $customer->ID,
        'first_name' => $customer->FirstName,
        'surname' => $customer->Surname,
        'email' => $customer->Email,
        'picture_url' => $customer->Picture()->AbsoluteLink()
      ]
    ]));
  }
}
