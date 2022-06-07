<?php

namespace Api;

use Api\Verify;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SilverStripe\Security\Member;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;

class ChangeEmailController extends Controller
{
  public function index(HTTPRequest $request)
  {
    // cek apakah ada token yang tersedia 
    $token = explode('/', $request->getURL())[2];

    if (is_null($token)) return $this->httpError(404);

    $verify = Verify::get()->filter('Token', $token)->first();

    // cek apakah token tersedia
    if (is_null($verify)) return $this->customise([
      'title' => 'Email verification link has expired.',
      'text' => 'Please login and resend the link.'
    ])->renderWith('Api/verify');

    // cek apakah token expired atau belum 
    if ($verify->isExpired()) {
      // jika token telah expired, hapus saja
      $verify->delete();
      return $this->customise([
        'title' => 'Email verification link has expired.',
        'text' => 'Please login and resend the link.'
      ])->renderWith('Api/verify');
    }

    $decoded = JWT::decode($token, new Key(Environment::getEnv('SECRET_KEY'), 'HS256'));

    // ambil member
    $member = Member::get_by_id($verify->MemberID);
    // set email member ke email baru  
    $member->Email = $decoded->email;
    try {
      $member->write();
      // jika berhasil diganti, hapus token
      $verify->delete();
      return $this->customise([
        'title' => 'Your email has been verified.',
        'text' => 'You can now login with your new email.'
      ])->renderWith('Api/change_email');
    } catch (Exception $e) {
      return $this->customise([
        'title' => 'Sorry Something went wrong.',
        'text' => 'Please try again later.'
      ])->renderWith('Api/change_email');
    }
  }
}
