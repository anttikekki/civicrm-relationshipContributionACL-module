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
  if($page instanceof CRM_Contribute_Page_ContributionPage) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->manageContributionsPageRunHook($page);
  }
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
  if($form instanceof CRM_Contribute_Form_Search) {
    $worker = RelationshipContributionACLWorker::getInstance();
    $worker->contributionSearchAlterTemplateHook($form);
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
  $url = $params[29]["child"][43]["attributes"]["url"];
  $url = $url . "&crmRowCount=9999999";
  $params[29]["child"][43]["attributes"]["url"] = $url;
}