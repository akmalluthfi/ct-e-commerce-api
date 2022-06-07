<?php

use Api\Merchant;
use SilverStripe\Admin\ModelAdmin;

class MerchantAdmin extends ModelAdmin
{
  private static $url_segment = 'merchants';

  private static $menu_title = 'Merchants';

  private static $menu_icon_class = 'font-icon-torsos-all';

  private static $managed_models = [
    Merchant::class
  ];
}
