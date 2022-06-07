<?php

use Api\MerchantCategory;
use SilverStripe\Admin\ModelAdmin;

class MerchantCategoryAdmin extends ModelAdmin
{
  private static $url_segment = 'merchants_categories';

  private static $menu_title = 'Merchants categories';

  private static $menu_icon_class = 'font-icon-tags';

  private static $managed_models = [
    MerchantCategory::class
  ];
}
