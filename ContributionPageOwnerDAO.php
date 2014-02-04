<?php

/**
* DAO for civicrm_contribution_page_owner table
*/
class ContributionPageOwnerDAO {
  /**
  * Insert or updates contribution page owner contact id to civicrm_contribution_page_owner table.
  * Update is done if row already exists. Does nothing if $owner_contact_id is not number larger than zero.
  *
  * @param string|int $contribution_page_id Contribution page id
  * @param string|int $owner_contact_id Owner Contact id
  */
  public static function insertOrUpdateOwnerContactId($contribution_page_id, $owner_contact_id) {
    $oldOwnerID = self::loadOwnerContactId($contribution_page_id);
    
    if($oldOwnerID == 0) {
      self::insertOwnerContactId($contribution_page_id, $owner_contact_id);
    }
    else {
      self::updateOwnerContactId($contribution_page_id, $owner_contact_id);
    }
  }
  
  /**
  * Insert contribution page owner contact id to civicrm_contribution_page_owner table.
  * Does nothing if $owner_contact_id is not number larger than zero.
  *
  * @param string|int $contribution_page_id Contribution page id
  * @param string|int $owner_contact_id Owner Contact id
  */
  public static function insertOwnerContactId($contribution_page_id, $owner_contact_id) {
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
  public static function updateOwnerContactId($contribution_page_id, $owner_contact_id) {
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
  public static function loadOwnerContactId($contribution_page_id) {
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
  public static function loadAllContributionPagesOwnerContactId() {
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
}