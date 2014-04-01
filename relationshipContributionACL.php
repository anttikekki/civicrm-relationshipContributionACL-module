<?php

require_once 'RelationshipContributionACLWorker.php';

/**
* Implements CiviCRM 'install' hook.
*/
function relationshipContributionACL_civicrm_install() {
  //Add table for Contribution page owner info
  $sql = "
    CREATE TABLE IF NOT EXISTS civicrm_contribution_page_owner (
      contribution_page_id int(10) unsigned NOT NULL COMMENT 'Contribution page id',
      owner_contact_id int(10) unsigned NOT NULL COMMENT 'Contribution page owner contact id',
      PRIMARY KEY (`contribution_page_id`)
    ) ENGINE=InnoDB;
  ";
  CRM_Core_DAO::executeQuery($sql);
  
  //Add table for configuration
  $sql = "
    CREATE TABLE IF NOT EXISTS civicrm_relationshipcontributionacl_config (
      config_key varchar(255) NOT NULL,
      config_value varchar(255) NOT NULL,
      PRIMARY KEY (`config_key`)
    ) ENGINE=InnoDB;
  ";
  CRM_Core_DAO::executeQuery($sql);
}

/**
* Implemets CiviCRM 'pageRun' hook.
*
* @param CRM_Core_Page $page Current page.
*/
function relationshipContributionACL_civicrm_pageRun(&$page) {
  //Manage contributin pages
  if($page instanceof CRM_Contribute_Page_ContributionPage) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->manageContributionsPageRunHook($page);
  }
  //Contribution edit
  else if($page instanceof CRM_Contribute_Page_Tab) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contributionPageRunHook($page);
  }
}

/**
* Implements CiviCRM 'buildForm' hook.
*
* @param string $formName Name of current form.
* @param CRM_Core_Form $form Current form.
*/
function relationshipContributionACL_civicrm_buildForm($formName, &$form) {
  //Contribution page edit
  if($form instanceof CRM_Contribute_Form_ContributionPage) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contributionPageBuildFormHook($form);
  }
}

/**
* Implemets CiviCRM 'alterTemplateFile' hook.
*
* @param String $formName Name of current form.
* @param CRM_Core_Form $form Current form.
* @param CRM_Core_Form $context Page or form.
* @param String $tplName The file name of the tpl - alter this to alter the file in use.
*/
function relationshipContributionACL_civicrm_alterTemplateFile($formName, &$form, $context, &$tplName) {
  //Contribution search
  if($form instanceof CRM_Contribute_Form_Search) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contributionSearchAlterTemplateHook($form);
  }
  //Contact Contributions tab
  else if($form instanceof CRM_Contribute_Page_Tab) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contactContributionTabAlterTemplateHook($form);
  }
  //Contact main page
  else if($form instanceof CRM_Contact_Page_View_Summary) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contactMainPageAlterTemplateFileHook($form);
  }
  //Contribution dashboard
  else if($form instanceof CRM_Contribute_Page_DashBoard) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contributionDashboardAlterTemplateHook($form);
  }
  //Contribution reports
  else if(RelationshipContributionACLWorker::isContributionReportClassName($formName)) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contributionReportsAlterTemplateHook($form);
  }
  //Extension admin page
  else if($form instanceof Admin_Page_RelationshipContributionACLAdmin) {
    $res = CRM_Core_Resources::singleton();
    $res->addScriptFile('com.github.anttikekki.relationshipContributionACL', 'Admin/Page/admin.js');
    
    //Add CMS neutral ajax callback URLs
    $res->addSetting(array('relationshipContributionACL' => 
      array(
        'getConfigAjaxURL' =>  CRM_Utils_System::url('civicrm/relationshipContributionACL/settings/ajax/getConfig'),
        'saveConfigRowAjaxURL' =>  CRM_Utils_System::url('civicrm/relationshipContributionACL/settings/ajax/saveConfigRow'),
        'deleteConfigRowAjaxURL' =>  CRM_Utils_System::url('civicrm/relationshipContributionACL/settings/ajax/deleteConfigRow')
      )
    ));
  }
}

/**
* Implements CiviCRM 'validateForm' hook.
*
* @param string $formName Name of current form.
* @param array $fields Array of name value pairs for all 'POST'ed form values
* @param array $files Array of file properties as sent by PHP POST protocol
* @param CRM_Core_Form $form Current form.
* @param array $errors Reference to the errors array. All errors will be added to this array
*/
function relationshipContributionACL_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors){
  if($form instanceof CRM_Contribute_Form_ContributionPage_Settings) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->validateFormHook($formName, $fields, $files, $form, $errors);
  }
}

/**
* Implements CiviCRM 'postSave' hook for civicrm_contribution_page table. 
* Form postProcess hook can not be used because it is not triggered when creating new 
* contribution page.
*
* @param CRM_Contribute_DAO_ContributionPage $dao Dao that is used to save Contribution page
*/
function relationshipContributionACL_civicrm_postSave_civicrm_contribution_page(&$dao) {
  if($dao instanceof CRM_Contribute_DAO_ContributionPage) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contributionPagePostSaveHook($dao);
  }
}

/**
* Implements CiviCRM 'postSave' hook for civicrm_contribution table.
*
* @param CRM_Contribute_DAO_Contribution $dao Dao that is used to save Contribution
*/
function relationshipContributionACL_civicrm_postSave_civicrm_contribution(&$dao) {
  if($dao instanceof CRM_Contribute_DAO_Contribution) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contributionPostSaveHook($dao);
  }
}

/**
* Implemets CiviCRM 'navigationMenu' hook. Alters navigation menu to 
* remove Pager from 'Manage Contribution pages' page. Pager is broken because of row 
* filtering done by this module.
*
* Menu rebuild is required to make this work.
*
* @param Array $params Navigation menu structure.
*/
function relationshipContributionACL_civicrm_navigationMenu(&$params) {
  //Edit Manage Contribution pages menu
  $url = $params[29]["child"][43]["attributes"]["url"];
  $url = $url . "&crmRowCount=9999999";
  $params[29]["child"][43]["attributes"]["url"] = $url;
  
  /*
  * Add admin menu for extension
  */
  //Find last index of Administer menu children
  $maxKey = max(array_keys($params[108]['child']));
  
  //Add extension menu as Admin menu last children
  $params[108]['child'][$maxKey+1] = array(
     'attributes' => array (
        'label'      => 'RelationshipContributionACL',
        'name'       => 'RelationshipContributionACL',
        'url'        => null,
        'permission' => null,
        'operator'   => null,
        'separator'  => null,
        'parentID'   => null,
        'navID'      => $maxKey+1,
        'active'     => 1
      ),
     'child' =>  array (
        '1' => array (
          'attributes' => array (
             'label'      => 'Settings',
             'name'       => 'Settings',
             'url'        => 'civicrm/relationshipContributionACL/settings',
             'permission' => 'administer CiviCRM',
             'operator'   => null,
             'separator'  => 1,
             'parentID'   => $maxKey+1,
             'navID'      => 1,
             'active'     => 1
              ),
          'child' => null
        )
      )
  );
}

/**
* Implemets CiviCRM 'config' hook.
*
* @param object $config the config object
*/
function relationshipContributionACL_civicrm_config(&$config) {
  $template =& CRM_Core_Smarty::singleton();
  $extensionDir = dirname(__FILE__);
 
  // Add extension template directory to the Smarty templates path
  if (is_array($template->template_dir)) {
    array_unshift($template->template_dir, $extensionDir);
  }
  else {
    $template->template_dir = array($extensionDir, $template->template_dir);
  }

  //Add extension folder to included folders list so that Ajax php is found whe accessin it from URL
  $include_path = $extensionDir . DIRECTORY_SEPARATOR . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);
}

/**
* Implemets CiviCRM 'xmlMenu' hook.
*
* @param array $files the array for files used to build the menu. You can append or delete entries from this file. 
* You can also override menu items defined by CiviCRM Core.
*/
function relationshipContributionACL_civicrm_xmlMenu( &$files ) {
  //Add Ajax and Admin page URLs to civicrm_menu table so that they work
  $files[] = dirname(__FILE__)."/menu.xml";
}