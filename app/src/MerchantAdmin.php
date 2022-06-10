<?php

use Api\Merchant;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

class MerchantAdmin extends ModelAdmin
{
  private static $url_segment = 'merchants';

  private static $menu_title = 'Merchants';

  private static $menu_icon_class = 'font-icon-torsos-all';

  private static $managed_models = [
    Merchant::class
  ];

  /**
   * @param Int $id
   * @param FieldList $fields
   * @return Form
   */
  public function getEditForm($id = null, $fields = null)
  {
    $form = parent::getEditForm($id, $fields);

    // get field name (one of managed models)
    $fieldName = $this->sanitiseClassName($this->modelClass);
    // get grid fields belongs to the above field name
    $grid = $form->Fields()->dataFieldByName($fieldName);

    // set config
    $config = GridFieldConfig_RecordEditor::create();
    $config->addComponents([
      new GridFieldImportButton('before'),
      new GridFieldExportButton('before'),
      new GridFieldPrintButton('before'),
    ]);

    $config->removeComponentsByType([
      GridFieldPageCount::class,
      GridFieldPaginator::class,
      GridFieldAddNewButton::class,
      GridFieldFilterHeader::class,
      GridFieldDeleteAction::class
    ]);

    // set config to grid 
    $grid->setConfig($config);


    return $form;
  }
}
