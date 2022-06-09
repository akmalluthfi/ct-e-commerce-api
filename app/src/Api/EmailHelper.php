<?php

namespace Api;

use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\ArrayList;

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

  public static function sendEmailApproved()
  {
    $email = Email::create();
    $email->setHTMLTemplate('Email\\email_approved');
    $email->setFrom('no-reply@admin.com', 'noreply');
    $email->setTo(Environment::getEnv('ADMIN_EMAIL'));
    $email->setSubject('New merchants has registered');

    return $email->send();
  }

  public static function sendOrderConfirmation($to, $data)
  {
    $email = Email::create();
    $email->setHTMLTemplate('Email\\order_confirm');
    $email->setFrom('no-reply@admin.com', 'noreply');
    $email->setData([
      'product' => new ArrayList($data['product']),
      'total' => $data['total']
    ]);
    $email->setTo($to);
    $email->setSubject('new order notification');

    return $email->send();
  }

  public static function sendOrderRejected($to, $data)
  {
    $email = Email::create();
    $email->setHTMLTemplate('Email\\order_rejected');
    $email->setFrom('no-reply@admin.com', 'noreply');
    $email->setData([
      'product' => new ArrayList($data['order_details']),
      'total' => $data['total'],
      'merchant' => $data['merchant'],
      'customer' => $data['customer'],
    ]);
    $email->setTo($to);
    $email->setSubject('Order Rejected');

    return $email->send();
  }

  public static function sendOrderAccepted($to, $data)
  {
    $email = Email::create();
    $email->setHTMLTemplate('Email\\order_accepted');
    $email->setFrom('no-reply@admin.com', 'noreply');
    $email->setData([
      'product' => new ArrayList($data['order_details']),
      'total' => $data['total'],
      'merchant' => $data['merchant'],
      'customer' => $data['customer'],
    ]);
    $email->setTo($to);
    $email->setSubject('Order Accepted');

    return $email->send();
  }
}
