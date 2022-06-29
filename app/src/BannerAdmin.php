<?php

use SilverStripe\Admin\ModelAdmin;

class BannerAdmin extends ModelAdmin
{
  private static $url_segment = 'banners';

  private static $menu_title = 'Banners';

  private static $menu_icon_class = 'font-icon-block-carousel';

  private static $managed_models = [
    Banner::class
  ];
}
