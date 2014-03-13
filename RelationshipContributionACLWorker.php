<?php

/**
* Only import worker if it is not already loaded. Multiple imports can happen
* because relationshipACL and relationshipEvenACL modules uses same worker. 
*/
if(class_exists('RelationshipACLQueryWorker') === false) {
  require_once "RelationshipACLQueryWorker.php";
}
RelationshipACLQueryWorker::checkVersion("1.1");

/**
* Only import worker if it is not already loaded. Multiple imports can happen
* because relationshipACL and relationshipEvenACL modules uses same worker. 
*/
if(class_exists('CustomFieldHelper') === false) {
  require_once "CustomFieldHelper.php";
}

/**
* Only import phpQuery if it is not already loaded. Multiple imports can happen
* because relationshipEvenACL module uses same phpQuery. 
*/
if(class_exists('phpQuery') === false) {
  require_once "phpQuery.php";
}

require_once "ContributionPageOwnerDAO.php";


/**
* relationshipContributionACL module helper worker.
*
* Adds new Contribution page owner field to admin form Title tab.
*/
class RelationshipContributionACLWorker {

  /**
  * Singleton instace of this worker
  *
  * @var RelationshipContributionACLWorker
  */
  private static $instance = null;

  /**
  * Config key for civicrm_relationshipContributionACL_config table. This key 
  * stores name of Contribution Custom field group that stores contribution owner contact id.
  */
  const CONFIG_KEY_CONTRIBUTION_OWNER_CUSTOM_GROUP_NAME = "contributionOwnerCustomGroupName";

  /**
  * Executed when any Contribution Report is displayed
  *
  * @param CRM_Report_Form $form COntribution Report
  */
  public function contributionReportsAlterTemplateHook(&$form) {
    $this->filterContributionReportCriteriaEvents($form);
    $this->filterContributionReportResultRows($form);
  }
  
  /**
  * Executed when Contributions Dashboard is built.
  * Filters contribution rows based on Contribution page owner.
  *
  * @param CRM_Contribute_Page_DashBoard $form Contribution Dashboard
  */
  public function contributionDashboardAlterTemplateHook(&$form) {
    $this->filterContributionsSearchFormResults($form);
  }
  
  /**
  * Executed when Contact Contributions tab is built.
  * Filters contribution rows based on Contribution page owner.
  *
  * @param CRM_Contribute_Page_Tab $form Contact Contributions tab
  */
  public function contactContributionTabAlterTemplateHook(&$form) {
    $this->filterContributionsSearchFormResults($form);
  }
  
  /**
  * Executed when Contact main page is built.
  *
  * @param CRM_Contact_Page_View_Summary $form Contact main page
  */
  public function contactMainPageAlterTemplateFileHook(&$form) {
    CRM_Core_Resources::singleton()->addScriptFile('com.github.anttikekki.relationshipContributionACL', 'contactActivityTabContributionFiltering.js');
    
    //Add array of Contact Contributon Page ids for contributions to be used in Activity tab data filtering
    $contactId = (int) $form->getTemplate()->get_template_vars("contactId");
    $contributionPageIdForContributionId = $this->getContactContributionsContributionPageIds($contactId);
    CRM_Core_Resources::singleton()->addSetting(array('relationshipContributionACL' => array('contributionPageIdForContributionId' => json_encode($contributionPageIdForContributionId))));
    
    //Add array of allowed Contribution page ids to be used in Activity tab data filtering
    $allowedContributionPageIds = $this->getAllowedContributionPageIds();
    CRM_Core_Resources::singleton()->addSetting(array('relationshipContributionACL' => array('allowedContributionPageIds' => $allowedContributionPageIds)));
  }

  /**
  * Executed when Contribution admin page is built.
  *
  * Adds info of contribution page owner to JavaScript from 
  * custom civicrm_contribution_page_owner table. Also adds ownerContactName.js file to page load 
  * so it can create new Owner Name input to form.
  *
  * @param CRM_Contribute_Form_ContributionPage $form Form
  */
  public function contributionPageBuildFormHook(&$form) {
    $this->checkContributionPageEditPermission($form);
  
    $contributionPageId = $form->get('id');
    $ownerContactId = ContributionPageOwnerDAO::loadOwnerContactId($contributionPageId);
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
  * Executed when Contribution info page (user contribution) is built.
  * Checks logged in user rights to edit contribution.
  *
  * @param CRM_Contribute_Page_Tab $page Form
  */
  public function contributionPageRunHook(&$page) {
    /*
    * CRM_Contribute_Page_Tab is also used to lead snippets by Ajax. 
    * Lets only check permissions for main page and not for Ajax snippets.
    */
    if(isset($_GET["snippet"])) {
      return;
    }
  
    $this->checkContributionEditPermission($page);
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
  public function contributionPagePostSaveHook(&$dao) {
    $contributionPageId = $dao->id;
    $ownerContactName = isset($_POST['ownerContactName']) ? $_POST['ownerContactName'] : NULL;
    
    $ownerContactId = $this->getContactIdForName($ownerContactName);
    ContributionPageOwnerDAO::insertOrUpdateOwnerContactId($contributionPageId, $ownerContactId);
  }
  
  /**
  * Executed when Contribution is saved to civicrm_contribution table.
  *
  * Updates contribution owner organisation custom field to Contribution page owner.
  *
  * @param CRM_Contribute_DAO_Contribution $dao Dao that is used to save Contribution
  */
  public function contributionPostSaveHook(&$dao) {
    $contributionId = $dao->id;
    $contributionPageId = $dao->contribution_page_id;
    $ownerContactId = ContributionPageOwnerDAO::loadOwnerContactId($contributionPageId);
    
    //If Contribution page has no owner set then abort
    if($ownerContactId == 0) {
      return;
    }
  
    //Update custom field to Contribution page owner contact id
    $worker = new CustomFieldHelper($this->getContributionOwnerCustomGroupNameFromConfig());
    $worker->insertOrUpdateValue($contributionId, $ownerContactId);
  }
  
  /**
  * Executed when Manage contribution pages page is built.
  *
  * Iterates 'row' array from template and removes Contribution pages where current logged in user does not have 
  * editing rights. Editing rights are based on relationship tree.
  *
  * @param CRM_Contribute_Page_ContributionPage CiviCRM Page for Manage contribution pages page
  */
  public function manageContributionsPageRunHook(&$page) {
    $this->checkPagerRowCount(9999999);
  
    $template = $page->getTemplate();
    $rows = $template->get_template_vars("rows");
  
    $this->filterContributionPageRows($rows);
    
    $page->assign("rows", $rows);
  }
  
  /**
  * Executed when Contribution search form is built.
  *
  * Iterates 'row' array from template and removes Contributions that belongs to Contribution page that current logged in user does not have 
  * editing rights. Editing rights are based on relationship tree.
  *
  * @param CRM_Contribute_Form_Search CiviCRM Page for Contribution search
  */
  public function contributionSearchAlterTemplateHook(&$form) {
    $this->filterContributionsSearchFormResults($form);
    
    //JavaScript adds 'limit=500' to contribution search form action URL to increase pager page size.
    CRM_Core_Resources::singleton()->addScriptFile('com.github.anttikekki.relationshipContributionACL', 'contributionSearchPagerFix.js');
  }
  
  /**
  * Iterates 'row' array from template and removes Contributions that belongs to Contribution page that current logged in user does not have 
  * editing rights. Editing rights are based on relationship tree.
  *
  * @param CRM_Contribute_Form_Search|CRM_Contribute_Page_Tab CiviCRM Page for Contribution search
  */
  private function filterContributionsSearchFormResults(&$form) {
    $template = $form->getTemplate();
    $rows = $template->get_template_vars("rows");
    
    //If there are no contribution search results (this happens before search) do not continue
    if(!is_array($rows)) {
      return;
    }
  
    $this->filterContributions($rows);
    $template->assign("rows", $rows);
  }
  
  /**
  * Iterates rows array from template and removes Contributions that belongs to Contribution page where current logged in user does not have 
  * editing rights. Editing rights are based on relationship tree. Contribution page owner is detemined from 
  * this module custom civicrm_contribution_page_owner table.
  *
  * @param array $rows Array of Contributions
  */
  private function filterContributions(&$rows) {    
    //If there are no contribution search results (this happens before search) do not continue
    if(!is_array($rows)) {
      return;
    }
    
    //Find all contribution ids
    $contributionIds = array();
    foreach ($rows as $index => &$row) {
      $contributionIds[] = $row["contribution_id"];
    }
    
    $allowedContributionIds = $this->getAllowedContributionPageContributionIds($contributionIds);
    
    foreach ($rows as $index => &$row) {
      $contributionId = $row["contribution_id"];
      
      if(!in_array($contributionId, $allowedContributionIds)) {
        unset($rows[$index]);
      }
    }
  }
  
  /**
  * Iterates array of Contribution ids and removes Contributions ids that belongs to Contribution pages where current logged in user does not have 
  * editing rights. Editing rights are based on relationship tree.
  *
  * @param array $contributionIds Array of Contribution ids
  * @return array Array of allowed Contribution ids
  */
  private function getAllowedContributionPageContributionIds($contributionIds) {
    /*
    * Find Contribution page ids for contributions. Page id is NULL if contribution is for Event.
    * Return value array key is Contribution id and value is Contribution page id
    */
    $contributionPageIds = $this->getContributionPageIdsForContributionIds($contributionIds);
    $allowedContributionPageIds = $this->getAllowedContributionPageIds();
    
    foreach ($contributionIds as $index => &$contributionId) {
      /*
      * Contribution is for Event if Contribution page id is NULL. 
      * Event Contributions are filtered by relationshipEventACL module.
      */
      if(!isset($contributionPageIds[$contributionId])) {
        continue;
      }
      
      $contributionPageId = $contributionPageIds[$contributionId];
      
      //If logged in user contact ID is not allowed to edit Contribution page, remove Contribution from array
      if(!in_array($contributionPageId, $allowedContributionPageIds)) {
        unset($contributionIds[$index]);
      }
    }
    
    return $contributionIds;
  }
  
  /**
  * Iterates array of Contribution Page ids and removes Contributions Page ids that  
  * current logged in user does not have editing rights. Editing rights are based on relationship tree.
  *
  * @param array $contributionPageIds Array of Contribution Page ids. If null or missingg, all Contribution Page ids are searched.
  * @return array Array of allowed Contribution Page ids
  */
  private function getAllowedContributionPageIds($contributionPageIds = NULL) {
    $currentUserContactID = $this->getCurrentUserContactID();
    
    //All contact IDs the current logged in user has rights to edit through relationships
    $worker = RelationshipACLQueryWorker::getInstance();
    $allowedContactIDs = $worker->getContactIDsWithEditPermissions($currentUserContactID);
    
    //Array with Contribution page ID as key and owner contact ID as value
    $ownerMap = ContributionPageOwnerDAO::loadAllContributionPagesOwnerContactId();
    
    //If set of Contribution page ids is not specified, load all Contribution page ids
    if(!isset($contributionPageIds)) {
      $sql = "
        SELECT id  
        FROM civicrm_contribution_page
      ";
      
      $dao = CRM_Core_DAO::executeQuery($sql);
      
      $contributionPageIds = array();
      while ($dao->fetch()) {
        $contributionPageIds[] = $dao->id;
      }
    }
    
    foreach ($contributionPageIds as $index => &$contributionPageId) {    
      //Skip Contribution pages that does not yet have owner info. These are always visible.
      if(!array_key_exists($contributionPageId, $ownerMap)) {
        continue;
      }
      
      //Get Contribution page owner contact ID from civicrm_contribution_page_owner table
      $ownerContactID = $ownerMap[$contributionPageId];
      
      //If logged in user contact ID is not allowed to edit Contribution page, remove Contribution from array
      if(!in_array($ownerContactID, $allowedContactIDs)) {
        unset($contributionPageIds[$index]);
      }
    }
    
    return $contributionPageIds;
  }
  
  /**
  * Checks that Manage Contribution pages URL contains crmRowCount parameter. 
  * If not, do redirect to same page with crmRowCount paramer. crmRowCount is needed 
  * to remove pager so all rows are always visible. Pager is broken because this module 
  * filters rows after pager is constructed.
  *
  * @param int|string $pagerPageSize Pager page max row count
  */
  private function checkPagerRowCount($pagerPageSize) {
    $currentURL = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    if(!isset($_GET["crmRowCount"])) {
      CRM_Utils_System::redirect($currentURL . "&crmRowCount=" . $pagerPageSize);
    }
  }
  
  /**
  * Check if current logged in user has rights to edit selected Contribution page. Show fatal error if no permission.
  *
  * @param CRM_Contribute_Form_ContributionPage $form Form for Contribution page editing
  */
  private function checkContributionPageEditPermission(&$form) {
    $contributonPageId = $form->get('id');
    
    $rows = array();
    $rows[$contributonPageId] = array();
    $this->filterContributionPageRows($rows);
    
    if(count($rows) === 0) {
      CRM_Core_Error::fatal(ts('You do not have permission to view this Contributon page'));
    }
  }
  
  /**
  * Check if current logged in user has rights to edit selected Contribution. Show fatal error if no permission.
  *
  * @param CRM_Contribute_Page_Tab $page Page for Contribution editing
  */
  private function checkContributionEditPermission(&$page) {
    $contributionId = $page->_id;
    $dao = new CRM_Contribute_DAO_Contribution();
    $dao->get("id", $contributionId);
    $contributonPageId = $dao->contribution_page_id;
    
    //Null Contribution page means Event regiteration contribution
    if(!isset($contributonPageId)) {
      /*
      * Event participation contribution edit permissions check is done by 
      * relationshipEventACL module (if present).
      */
      return;
    }
    
    $rows = array();
    $rows[$contributonPageId] = array();
    $this->filterContributionPageRows($rows);
    
    if(count($rows) === 0) {
      CRM_Core_Error::fatal(ts('You do not have permission to edit this Contributon'));
    }
  }
  
  /**
  * Iterates rows array from template and removes Contribution pages where current logged in user does not have 
  * editing rights. Editing rights are based on relationship tree. Contribution page owner is detemined from 
  * this module custom civicrm_contribution_page_owner table.
  *
  * @param array $rows Array of Contribution pages
  */
  private function filterContributionPageRows(&$rows) {
    $allowedContributionPageIds = $this->getAllowedContributionPageIds();
    
    foreach ($rows as $contibutionPageId => &$row) {
      //If logged in user contact ID is not allowed to edit Contribution page, remove page from array
      if(!in_array($contibutionPageId, $allowedContributionPageIds)) {
        unset($rows[$contibutionPageId]);
      }
    }
  }
  
  /**
  * Loads contact id for sort_name column value.
  *
  * @param string $contactName Contact name as it is in sort_name column
  * @return int Contact id. Zero if $contactName is empty
  */
  private function getContactIdForName($contactName) {
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
  private function getContactNameForId($contactId) {
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
  * Return Contribution custom field group name that is used to store contribution 
  * owner contact id.
  *
  * @return string Custom field group title name.
  */
  private function getContributionOwnerCustomGroupNameFromConfig() {
    $sql = "
      SELECT config_value  
      FROM civicrm_relationshipContributionACL_config
      WHERE config_key = '".RelationshipContributionACLWorker::CONFIG_KEY_CONTRIBUTION_OWNER_CUSTOM_GROUP_NAME."'
    ";
    
    return CRM_Core_DAO::singleValueQuery($sql);
  }
  
  /**
  * Returns current logged in user contact ID.
  *
  * @return int Contact ID
  */
  private function getCurrentUserContactID() {
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
  
  /**
  * Query Contribution page ids for contribution id.
  *
  * @param array $contributionIds Contribution ids
  * @return array Array where key is contribution id and value is Contribution page id
  */
  private function getContributionPageIdsForContributionIds($contributionIds) {
    //Remove values that are not numeric
    $contributionIds = array_filter($contributionIds, "is_numeric");
    
    if(count($contributionIds) == 0) {
      return array();
    }
  
    $sql = "
      SELECT id, contribution_page_id  
      FROM civicrm_contribution
      WHERE id IN (". implode(",", $contributionIds) .")
    ";
    
    $dao = CRM_Core_DAO::executeQuery($sql);
    
    $result = array();
    while ($dao->fetch()) {
      $result[$dao->id] = $dao->contribution_page_id;
    }
    
    return $result;
  }
  
  /**
  * Query Contribution page ids for Contact id contributions.
  *
  * @param int|string $contactId Contribution ids
  * @return array Array where key is contribution id and value is Contribution page id
  */
  private function getContactContributionsContributionPageIds($contactId) {
    $contactId = (int) $contactId;
  
    $sql = "
      SELECT id, contribution_page_id  
      FROM civicrm_contribution
      WHERE contact_id = $contactId
        AND contribution_page_id IS NOT NULL
    ";
    
    $dao = CRM_Core_DAO::executeQuery($sql);
    
    $result = array();
    while ($dao->fetch()) {
      $result[$dao->id] = $dao->contribution_page_id;
    }
    
    return $result;
  }
  
  /**
  * Filter Contribution Reports Contribution page selection criteria.
  * Modification has to done to html to get it to work. Editing filters-array 
  * in template variables has no effects because html for select has already 
  * been generated.
  *
  * @param CRM_Report_Form $form Contribution Report
  */
  private function filterContributionReportCriteriaEvents(&$form) {
    $template = $form->getTemplate();
    $reportform = $template->get_template_vars("form");
    
    $fieldName;
    if(isset($reportform["contribution_page_id_value"])) {
      $fieldName = "contribution_page_id_value";
    }
    else {
      return;
    }
    
    $doc = phpQuery::newDocumentHTML($reportform[$fieldName]['html']);
    
    //Find all Contribution page ids
    $contributionPageIds = array();
    foreach ($doc->find("option") as $selectOption) {
      $contributionPageIds[] = (int) pq($selectOption)->val();
    }

    $allowedContributionIds = $this->getAllowedContributionPageIds($contributionPageIds);
    
    foreach ($doc->find("option") as $selectOption) {
      $contributionPageId = (int) pq($selectOption)->val();
      if(!in_array($contributionPageId, $allowedContributionIds)) {
        pq($selectOption)->remove();
      }
    }
    
    $reportform[$fieldName]['html'] = $doc->getDocument();
    $template->assign("form", $reportform);
  }
  
  /**
  * Filter Contribution Reports result data
  *
  * @param CRM_Report_Form $form Contribution Report
  */
  private function filterContributionReportResultRows(&$form) {
    if($form instanceof CRM_Report_Form_Contribute_Detail) {
      $this->filterContributionDetailsReportResultRows($form);
    }
    else if($form instanceof CRM_Report_Form_Contribute_Bookkeeping) {
      $this->filterContributionBokkeepingReportResultRows($form);
    }
  }
  
  /**
  * Filter Contribution Details Report result data contributions
  *
  * @param CRM_Report_Form_Contribute_Detail $form Contribution Details Report
  */
  private function filterContributionDetailsReportResultRows(&$form) {
    $template = $form->getTemplate();
    $rows = $template->get_template_vars("rows");
    
    //Find all Contribution ids
    $contributionIds = array();
    foreach ($rows as $index => &$row) {
      $contributionIds[] = (int) $row["civicrm_contribution_contribution_id"];
    }

    $allowedContributionIds = $this->getAllowedContributionPageContributionIds($contributionIds);
    
    foreach ($rows as $index => &$row) {
      $contributionId = (int) $row["civicrm_contribution_contribution_id"];
      
      if(!in_array($contributionId, $allowedContributionIds)) {
        unset($rows[$index]);
      }
    }
    $template->assign("rows", $rows);
  }
  
  /**
  * Filter Contribution Bookkeeping Report result data contributions
  *
  * @param CRM_Report_Form_Contribute_Bookkeeping $form Contribution Bookkeeping Report
  */
  private function filterContributionBokkeepingReportResultRows(&$form) {
    $template = $form->getTemplate();
    $rows = $template->get_template_vars("rows");
    
    //Find all Contribution ids
    $contributionIds = array();
    foreach ($rows as $index => &$row) {
      $contributionIds[] = (int) $row["civicrm_contribution_id"];
    }

    $allowedContributionIds = $this->getAllowedContributionPageContributionIds($contributionIds);
    
    foreach ($rows as $index => &$row) {
      $contributionId = (int) $row["civicrm_contribution_id"];
      
      if(!in_array($contributionId, $allowedContributionIds)) {
        unset($rows[$index]);
      }
    }
    $template->assign("rows", $rows);
  }
  
  /**
  * Is given class name a Contribution Report class name?
  *
  * @param string $formName Class name
  * @return boolean True if given class name is a Contribution report
  */
  public static function isContributionReportClassName($formName) {
    $reportClassNames = array('CRM_Report_Form_Contribute_Detail', 'CRM_Report_Form_Contribute_Summary', 'CRM_Report_Form_Contribute_Bookkeeping');
    return in_array($formName, $reportClassNames);
  }
  
  /**
  * Call this method to get singleton RelationshipContributionACLWorker
  *
  * @return RelationshipContributionACLWorker
  */
  public static function getInstance() {
    if (!isset(static::$instance)) {
      static::$instance = new RelationshipContributionACLWorker();
    }
    return static::$instance;
  }
}