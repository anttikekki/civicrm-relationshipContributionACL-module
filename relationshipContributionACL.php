<?php

require_once 'RelationshipContributionACLWorker.php';

/**
* Implements CiviCRM 'buildForm' hook.
*
* @param String $formName Name of current form.
* @param CRM_Core_Form $form Current form.
*/
function relationshipContributionACL_civicrm_buildForm($formName, &$form) {
  if($form instanceof CRM_Contribute_Form_ContributionPage) {
    $contributionPageId = $form->get('id');
    
    $worker = new RelationshipContributionACLWorker();
    $ownerContactId = $worker->loadOwnerContactId($contributionPageId);
    $contactName = $worker->getContactNameForId($ownerContactId);
    
    //Add JavaScript that adds new "Owner contact" field to "Title" tab
    CRM_Core_Resources::singleton()->addScriptFile('com.github.anttikekki.relationshipContributionACL', 'ownerContactName.js');
     
    //If there is no saved contact, set input value to empty instead of null so the input wont show 'null'
    if(is_null($contactName)) {
      $contactName = '';
    }
    
    //Set value to JavaScript. This will be accesible by CRM.relationshipContributionACL.ownerContactName
    CRM_Core_Resources::singleton()->addSetting(array('relationshipContributionACL' => array('ownerContactName' => $contactName)));
  }
}

function relationshipContributionACL_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors){
  if($form instanceof CRM_Contribute_Form_ContributionPage_Settings) {
    $ownerContactName = isset($fields["ownerContactName"]) ? $fields["ownerContactName"] : NULL;
    
    $worker = new RelationshipContributionACLWorker();
    $ownerContactId = $worker->getContactIdForName($ownerContactName);
    
    if($ownerContactId == 0) {
      $errors['ownerContactName'] = ts( 'Valid Contact Name is a required' );
    }
  }
  //drupal_set_message("validate: ownerContactName: " . $fields["ownerContactName"] . var_export($form, true));
}

function relationshipContributionACL_civicrm_postSave_civicrm_contribution_page(&$dao) {
  if($dao instanceof CRM_Contribute_DAO_ContributionPage) {
    $contributionPageId = $dao->id;
    $ownerContactName = isset($_POST['ownerContactName']) ? $_POST['ownerContactName'] : NULL;
    
    $worker = new RelationshipContributionACLWorker();
    $ownerContactId = $worker->getContactIdForName($ownerContactName);
    $worker->insertOrUpdateOwnerContactId($contributionPageId, $ownerContactId);
  }
}