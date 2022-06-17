<?php

use Api\Api;
use SilverStripe\Admin\ModelAdmin;

class ApiAdmin extends ModelAdmin
{
  private static $url_segment = 'apis';

  private static $menu_title = 'Apis';

  private static $managed_models = [
    Api::class
  ];
}
