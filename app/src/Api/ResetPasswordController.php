<?php

namespace Api;

use Api\Verify;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

class ResetPasswordController extends Controller
{
  public function index(HTTPRequest $request)
  {
    // cek apakah token yang dikirm valid 
    $token = $request->param('token');

    if (is_null($token)) return $this->httpError(404);

    $verify = Verify::get()->filter('Token', $token)->first();
    // cek apakah token tersedia

    if (is_null($verify)) {
      return $this->customise([
        'title' => 'Email verification link has expired.',
        'text' => 'Please login and resend the link.'
      ])->renderWith('Api/verify');
    }

    // cek apakah token expired atau belum 
    if ($verify->isExpired()) {
      // hapus token 
      $verify->delete();
      return $this->customise(['id' => $verify->MemberID,])->renderWith('Api/verify');
    };

    // return halaman ubah password 
    return $this->customise([
      'token' => $token,
      'link' => 'http://localhost:8080' . BASE_URL . "/api/customers/change-password"
    ])->renderWith('Api/form_forget-password');
  }
}
