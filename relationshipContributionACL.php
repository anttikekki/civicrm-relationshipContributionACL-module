<?php

require_once 'RelationshipContributionACLWorker.php';

/**
* Implements CiviCRM 'buildForm' hook.
*
* @param String $formName Name of current form.
* @param CRM_Core_Form $form Current form.
*/
function relationshipContributionACL_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contribute_Form_ContributionPage_Settings') {
    $contributionPageId = $form->get('id');
    
    $worker = new RelationshipContributionACLWorker();
    $ownerContactId = $worker->loadOwnerContactId($contributionPageId);
    $contactName = $worker->getContactNameForId($ownerContactId);
     
     CRM_Core_Resources::singleton()->addScriptFile('com.github.anttikekki.relationshipContributionACL', 'ownerContactName.js');
     CRM_Core_Resources::singleton()->addSetting(array('relationshipContributionACL' => array('ownerContactName' => $contactName)));
  }
}

function relationshipContributionACL_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Contribute_Form_ContributionPage_Settings') {
    $contributionPageId = $form->get('id');
    $ownerContactName = $form->getSubmitValue('ownerContactName');
    
    //CRM_Core_Error::fatal(var_export($form, true));
    //CRM_Core_Error::fatal($ownerContactId);
    
    $worker = new RelationshipContributionACLWorker();
    $ownerContactId = $worker->getContactIdForName($ownerContactName);
    $worker->insertOrUpdateOwnerContactId($contributionPageId, $ownerContactId);
  }
}