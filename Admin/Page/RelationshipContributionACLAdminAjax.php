<?php

require_once "RelationshipContributionACLAdminDAO.php";

/**
* Ajax request listener for RelationshipContributionACL Admin page Ajax calls.
* This listener methods intercept URLs in form civicrm/relationshipContributionACL/settings/ajax/*. This is configured in menu.xml.
* All methods print JSON-response and terminates CiviCRM.
*/
class Admin_Page_RelationshipContributionACLAdminAjax {
  
  /**
  * Returns all rows from civicrm_relationshipcontributionacl_config table.
  * Listens URL civicrm/relationshipContributionACL/settings/ajax/getConfig.
  */
  public static function getConfig() {
    echo json_encode(RelationshipContributionACLAdminDAO::getAllConfigRows());
    CRM_Utils_System::civiExit();
  }
  
  /**
  * Saves (creates or updates) configuration row in civicrm_relationshipcontributionacl_config table.
  * Prints "ok" if save was succesfull. All other responses are error messages.
  * Listens URL civicrm/relationshipContributionACL/settings/ajax/saveConfigRow.
  *
  * Saved parameters are queried from $_GET.
  */
  public static function saveConfigRow() {
    echo RelationshipContributionACLAdminDAO::saveConfigRow($_GET);
    CRM_Utils_System::civiExit();
  }
  
  /**
  * Deletes configuration row from civicrm_relationshipcontributionacl_config table.
  * Prints "ok" if delete was succesfull.
  * Listens URL civicrm/relationshipContributionACL/settings/ajax/deleteConfigRow.
  *
  * Delete parameters are queried from $_GET.
  */
  public static function deleteConfigRow() {
    RelationshipContributionACLAdminDAO::deleteConfigRow($_GET);
    
    echo "ok";
    CRM_Utils_System::civiExit();
  }
}