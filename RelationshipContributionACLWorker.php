<?php

/**
* Only import worker if it is not already loaded. Multiple imports can happen
* because relationshipACL and relationshipEvenACL modules uses same worker. 
*/
if(class_exists('RelationshipACLQueryWorker') === false) {
  require_once "RelationshipACLQueryWorker.php";
}

/**
* relationshipContributionACL module helper worker.
*
* Adds new Contribution page owner field to admin form Title tab.
*/
class RelationshipContributionACLWorker {

  /**
  * Executed when Contribution admin page is built.
  *
  * Adds info of contribution page owner to JavaScript from 
  * custom civicrm_contribution_page_owner table. Also adds ownerContactName.js file to page load 
  * so it can create new Owner Name input to form.
  *
  * @param CRM_Core_Form $form Form
  */
  public function buildFormHook(&$form) {
    $contributionPageId = $form->get('id');
    $ownerContactId = $this->loadOwnerContactId($contributionPageId);
    $contactName = $this->getContactNameForId($ownerContactId);
    
    //Add JavaScript that adds new "Owner contact" field to "Title" tab
    CRM_Core_Resources::singleton()->addScriptFile('com.github.anttikekki.relationshipContributionACL', 'ownerContactName.js');
     
    //If there is no saved contact, set input value to empty instead of null so the input wont show 'null'
    if(is_null($contactName)) {
      $contactName = '';
    }
    
    //Set value to JavaScript. This will be accesible by CRM.relationshipContributionACL.ownerContactName
    CRM_Core_Resources::singleton()->addSetting(array('relationshipContributionACL' => array('ownerContactName' => $contactName)));
  }
  
  /**
  * Executed when Contribution admin page save is validated.
  *
  * Validates that Owner contact name is valid value from database. Shows error in form if not.
  *
  * @param string $formName Name of current form.
  * @param array $fields Array of name value pairs for all 'POST'ed form values
  * @param array $files Array of file properties as sent by PHP POST protocol
  * @param CRM_Core_Form $form Current form.
  * @param array $errors Reference to the errors array. All errors will be added to this array
  */
  public function validateFormHook($formName, &$fields, &$files, &$form, &$errors) {
    $ownerContactName = isset($fields["ownerContactName"]) ? $fields["ownerContactName"] : '';
    $ownerContactId = $this->getContactIdForName($ownerContactName);
    
    if($ownerContactId == 0) {
      /*
      * Owner Contact id is required. Message that is added to $errors array wont become visible because
      * ownerContactName field is not controlled trough Smarty templates. We add the error anyway so that
      * save is prohibited. Same message is also added to JavaScript so we can show it from there.
      */
      $message = ts( 'Valid Contact Name is a required' );
      $errors['ownerContactName'] = $message;
      CRM_Core_Resources::singleton()->addSetting(array('relationshipContributionACL' => array('ownerContactName_emptyErrorMessgage' => $message)));
    }
    
    //Add valid or invalid owner contact name back to JavaScript so it is displayed after errors
    CRM_Core_Resources::singleton()->addSetting(array('relationshipContributionACL' => array('ownerContactName' => $ownerContactName)));
  }
  
  /**
  * Executed when Contribution page is saved to civicrm_contribution_page table.
  *
  * Saves contribution page owner id to custom civicrm_contribution_page_owner table.
  *
  * @param CRM_Contribute_DAO_ContributionPage $dao Dao that is used to save Contribution page
  */
  public function postSaveHook(&$dao) {
    $contributionPageId = $dao->id;
    $ownerContactName = isset($_POST['ownerContactName']) ? $_POST['ownerContactName'] : NULL;
    
    $ownerContactId = $this->getContactIdForName($ownerContactName);
    $this->insertOrUpdateOwnerContactId($contributionPageId, $ownerContactId);
  }
  
  /**
  * Executed when Manage contribution pages page is built.
  *
  * Iterates 'row' array from template and removes Contribution pages where current logged in user does not have 
  * editing rights. Editing rights are based on relationship tree.
  *
  * @param CRM_Contribute_Page_ContributionPage CiviCRM Page for Manage contribution pages page
  */
  public function pageRunHook(&$page) {
    $template = $page->getTemplate();
    $rows = $template->get_template_vars("rows");
  
    $this->filterContributionPageRows($rows);
    
    $page->assign("rows", $rows);
  }
  
  /**
  * Iterates rows array from template and removes Contribution pages where current logged in user does not have 
  * editing rights. Editing rights are based on relationship tree. Contribution page owner is detemined from 
  * this module custom civicrm_contribution_page_owner table.
  *
  * @param array $rows Array of Contribution pages
  */
  public function filterContributionPageRows(&$rows) {
    $currentUserContactID = $this->getCurrentUserContactID();
    
    //Find all contact IDs the current logged in user has rights to edit through relationships
    $worker = new RelationshipACLQueryWorker();
    $allowedContactIDs = $worker->getContactIDsWithEditPermissions($currentUserContactID);
    
    //Array with Contribution page ID as key and owner contact ID as value
    $ownerMap = $this->loadAllContributionPagesOwnerContactId();
    
    foreach ($rows as $eventID => &$row) {
      //Skip Contribution pages that does not yet have owner info. These are always visible.
      if(!array_key_exists($eventID, $ownerMap)) {
        continue;
      }
      
      //Get Contribution page owner contact ID from civicrm_contribution_page_owner table
      $ownerContactID = $ownerMap[$eventID];
      
      //If logged in user contact ID is not allowed to edit Contribution page, remove page from array
      if(!in_array($ownerContactID, $allowedContactIDs)) {
        unset($rows[$eventID]);
      }
    }
  }
  
  /**
  * Insert or updates contribution page owner contact id to civicrm_contribution_page_owner table.
  * Update is done if row already exists. Does nothing if $owner_contact_id is not number larger than zero.
  *
  * @param string|int $contribution_page_id Contribution page id
  * @param string|int $owner_contact_id Owner Contact id
  */
  public function insertOrUpdateOwnerContactId($contribution_page_id, $owner_contact_id) {
    $oldOwnerID = $this->loadOwnerContactId($contribution_page_id);
    
    if($oldOwnerID == 0) {
      $this->insertOwnerContactId($contribution_page_id, $owner_contact_id);
    }
    else {
      $this->updateOwnerContactId($contribution_page_id, $owner_contact_id);
    }
  }
  
  /**
  * Insert contribution page owner contact id to civicrm_contribution_page_owner table.
  * Does nothing if $owner_contact_id is not number larger than zero.
  *
  * @param string|int $contribution_page_id Contribution page id
  * @param string|int $owner_contact_id Owner Contact id
  */
  public function insertOwnerContactId($contribution_page_id, $owner_contact_id) {
    $contribution_page_id = (int) $contribution_page_id;
    $owner_contact_id = (int) $owner_contact_id;
    
    //No contact id, do not insert row
    if($owner_contact_id == 0) {
      return;
    }
  
    $sql = "
      INSERT INTO civicrm_contribution_page_owner
      VALUES ($contribution_page_id, $owner_contact_id)
    ";
 
    CRM_Core_DAO::executeQuery($sql);
  }
  
  /**
  * Updates contribution page owner contact id to civicrm_contribution_page_owner table.
  * Does nothing if $owner_contact_id is not number larger than zero.
  *
  * @param string|int $contribution_page_id Contribution page id
  * @param string|int $owner_contact_id Owner Contact id
  */
  public function updateOwnerContactId($contribution_page_id, $owner_contact_id) {
    $contribution_page_id = (int) $contribution_page_id;
    $owner_contact_id = (int) $owner_contact_id;
    
    //No contact id, do not update row
    if($owner_contact_id == 0) {
      return;
    }
  
    $sql = "
      UPDATE civicrm_contribution_page_owner
      SET owner_contact_id = $owner_contact_id
      WHERE contribution_page_id = $contribution_page_id
    ";
 
    CRM_Core_DAO::executeQuery($sql);
  }
  
  /**
  * Loads single contribution page owner contact id from civicrm_contribution_page_owner table.
  *
  * @param string|int $contribution_page_id Contribution page id
  * @return int Contact id. Null if $contribution_page_id is not number larger than zero
  */
  public function loadOwnerContactId($contribution_page_id) {
    $contribution_page_id = (int) $contribution_page_id;
    
    if($contribution_page_id == 0) {
      return null;
    }
    
    $sql = "
      SELECT owner_contact_id  
      FROM civicrm_contribution_page_owner
      WHERE contribution_page_id = $contribution_page_id
    ";
    
    return (int) CRM_Core_DAO::singleValueQuery($sql);
  }
  
  /**
  * Loads all contribution pages owner contact ids from civicrm_contribution_page_owner table.
  *
  * @return array Associative array where key is Contribution page ID and value is contact ID.
  */
  public function loadAllContributionPagesOwnerContactId() {
    $sql = "
      SELECT contribution_page_id, owner_contact_id  
      FROM civicrm_contribution_page_owner
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    
    $result = array();
    while ($dao->fetch()) {
      $result[$dao->contribution_page_id] = $dao->owner_contact_id;
    }
    
    return $result;
  }
  
  /**
  * Loads contact id for sort_name column value.
  *
  * @param string $contactName Contact name as it is in sort_name column
  * @return int Contact id. Zero if $contactName is empty
  */
  public function getContactIdForName($contactName) {
    if(empty($contactName)) {
      return 0;
    }
    
    $sql = "
      SELECT id  
      FROM civicrm_contact
      WHERE sort_name = %1
    ";
    
    $params = array(1  => array( $contactName, 'String'));
    
    return (int) CRM_Core_DAO::singleValueQuery($sql, $params);
  }
  
  /**
  * Loads contact sort_name for contact id.
  *
  * @param string|int $contactId Contact id
  * @return String Contact sort_name. Null if $contactId is not number larger than zero
  */
  public function getContactNameForId($contactId) {
    $contactId = (int) $contactId;
    
    if($contactId == 0) {
      return null;
    }
    
    $sql = "
      SELECT sort_name  
      FROM civicrm_contact
      WHERE id = $contactId
    ";
    
    return CRM_Core_DAO::singleValueQuery($sql);
  }
  
  /**
  * Returns current logged in user contact ID.
  *
  * @return int Contact ID
  */
  public function getCurrentUserContactID() {
    global $user;
    $userID = $user->uid;

    $params = array(
      'uf_id' => $userID,
      'version' => 3
    );
    $result = civicrm_api( 'UFMatch','Get',$params );
    $values = array_values ($result['values']);
    $contact_id = $values[0]['contact_id'];
    
    return $contact_id;
  }
}