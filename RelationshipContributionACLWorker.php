<?php

class RelationshipContributionACLWorker {

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
  
  public function insertOrUpdateOwnerContactId($contribution_page_id, $owner_contact_id) {
    $oldOwnerID = $this->loadOwnerContactId($contribution_page_id);
    
    if($oldOwnerID == 0) {
      $this->insertOwnerContactId($contribution_page_id, $owner_contact_id);
    }
    else {
      $this->updateOwnerContactId($contribution_page_id, $owner_contact_id);
    }
  }
  
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
  
  public function getContactIdForName($contactName) {
    if(empty($contactName)) {
      return 0;
    }
    
    $sql = "
      SELECT id  
      FROM civicrm_contact
      WHERE sort_name = '$contactName'
    ";
    
    return (int) CRM_Core_DAO::singleValueQuery($sql);
  }
  
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
}