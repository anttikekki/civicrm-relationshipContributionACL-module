<?php

require_once 'RelationshipContributionACLWorker.php';

/**
* Implements CiviCRM 'buildForm' hook.
*
* @param string $formName Name of current form.
* @param CRM_Core_Form $form Current form.
*/
function relationshipContributionACL_civicrm_buildForm($formName, &$form) {
  if($form instanceof CRM_Contribute_Form_ContributionPage) {
    $worker = new RelationshipContributionACLWorker();
    $worker->buildFormHook($form);
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
    $worker = new RelationshipContributionACLWorker();
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
    $worker = new RelationshipContributionACLWorker();
    $worker->postSaveHook($dao);
  }
}