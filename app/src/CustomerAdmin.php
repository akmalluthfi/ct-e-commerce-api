<?php

use Api\Customer;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

class CustomerAdmin extends ModelAdmin
{
  private static $url_segment = 'customers';

  private static $menu_title = 'Customers';

  private static $menu_icon_class = 'font-icon-torsos-all';

  private static $managed_models = [
    Customer::class
  ];

  public function getEditForm($id = null, $fields = null)
  {
    $form = parent::getEditForm($id, $fields);

    $fieldName = $this->sanitiseClassName($this->modelClass);
    $grid = $form->Fields()->dataFieldByName($fieldName);

    $config = GridFieldConfig_RecordViewer::create();

    $config->addComponent(new GridFieldImportButton('before'));
    $config->addComponent(new GridFieldExportButton('before'));
    $config->addComponent(new GridFieldPrintButton('before'));

    $config->removeComponentsByType([
      GridFieldPageCount::class,
      GridFieldPaginator::class
    ]);

    $grid->setConfig($config);

    return $form;
  }
}
