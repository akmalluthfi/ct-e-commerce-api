<?php

namespace Api;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;

class VerifyController extends Controller
{
  public function index(HTTPRequest $request)
  {
    // cek apakah ada token yang tersedia 
    $token = $request->param('token');

    if (is_null($token)) return $this->httpError(404);

    $verify = Verify::get()->filter('Token', $token)->first();
    // cek apakah token tersedia
    if (is_null($verify)) return $this->customise([
      'title' => 'Email verification link has expired.',
      'text' => 'Please login and resend the link.'
    ])->renderWith('Api/verify');

    // cek apakah token expired atau belum 
    if ($verify->isExpired()) return $this->customise([
      'title' => 'Email verification link has expired.',
      'text' => 'Please login and resend the link.'
    ])->renderWith('Api/verify');

    // ubah isvalidate member id menjadi true
    $member = Member::get_by_id($verify->MemberID);
    // tampilkan halaman bahwa 
    $member->isValidated = true;
    try {
      $member->write();

      return $this->customise([
        'title' => 'Your email has been verified.',
        'text' => 'You can now login with your new account.'
      ])->renderWith('Api/verify');
    } catch (ValidationException $e) {
      return $this->customise([
        'title' => 'Sorry Something went wrong.',
        'text' => 'Please try again later.'
      ])->renderWith('Api/verify');
    }
  }
}
