<?php

namespace Api;

use SilverStripe\Control\Email\Email;

class EmailHelper
{
  public static function sendEmailValidation($to, $token)
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

  public static function sendEmailForgotPassword($to, $token)
  {
    $email = Email::create();
    $email->setHTMLTemplate('Email\\forget_password');
    $email->setData([
      'link' => 'http://localhost:8080' . BASE_URL . "/api/password_reset/$token"
    ]);
    $email->setFrom('no-reply@admin.com', 'noreply');
    $email->setTo($to);
    $email->setSubject('Reset your password');

    return $email->send();
  }

  public static function sendEmailChangeEmail($to, $token)
  {
    $email = Email::create();
    $email->setHTMLTemplate('Email\\change_email');
    $email->setData([
      'link' => 'http://localhost:8080' . BASE_URL . "/api/change_email/$token"
    ]);
    $email->setFrom('no-reply@admin.com', 'noreply');
    $email->setTo($to);
    $email->setSubject('Email verification');

    return $email->send();
  }
}
