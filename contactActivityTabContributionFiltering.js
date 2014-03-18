/**
* Filters activity rows from Contact Summary Activity tab Ajax result by removing Contribution activities that 
* belong to Contribution pages that current user has no permission to edit or see.
*
* Ajax result data do not contain contribution id directly but it is contained in Contribution 
* edit link URL (in id parameter) from where it is parsed.
*
* Example url: http://localhost/drupal/index.php?q=civicrm/contact/view/contribution&action=view&reset=1&id=15&cid=8&context=activity
* Contribution id is 15.
*/
cj(function ($) {
  'use strict';
  
  /**
  * Is given ajax setting from Contact Acrivity tab table ajax call?
  *
  * @param {array} ajaxSettings jQuery Ajax call settings array
  * @return {boolean} true if is Acrivity tab tab
  */
  function isActivityTabAjax(ajaxSettings) {
    return ajaxSettings.url.indexOf('civicrm/ajax/contactactivity') !== -1;
  }
  
  /**
  * Get parameter value from given URL?
  *
  * @param {string} url URL string
  * @param {string} parm Parameter that is searched from url.
  * @return {string} Parameter value. Null if value is not found.
  */
  function getParameterFromURL(url, parm) {
    //Code from http://stackoverflow.com/a/10625052
    var re = new RegExp("[?&]" + parm + "=([^&]+)(&|$)");
    var match = url.replace(/&amp;/g, '&').match(re);
    return(match ? match[1] : null);
  }
  
  /**
  * Searches Contribution id from Acrivity View-link URL.
  * Example url: http://localhost/drupal/index.php?q=civicrm/contact/view/contribution&action=view&reset=1&id=15&cid=8&context=activity
  *
  * @param {array} activity Array of activity values from Ajax.
  * @return {string} Participant id. Null if id is not found (meaning row is not Event participant activity).
  */
  function getActivityContributionId(activity) {
    var viewURL = activity[7];
    
    if(viewURL.indexOf('civicrm/contact/view/contribution') === -1) {
      return null;
    }
    
    return getParameterFromURL(viewURL, 'id');
  }
  
  /**
  * Filters activity rows from Ajax by removing Contribution activities that 
  * belong to Contribution pages that current user has no permission to edit or see.
  *
  * @param {object} ajaxData jQuery Ajax call result data
  */
  function filterActivityContributions(ajaxData) {
    //These are set in RelationshipContributionACLWorker.contactMainPageAlterTemplateFileHook()
    var contributionPageIdForContributionId = JSON.parse(CRM.relationshipContributionACL.contributionPageIdForContributionId);
    var allowedContributionPageIds = CRM.relationshipContributionACL.allowedContributionPageIds;
    
    //CiviCRM converts PHP Arrays to JSON. We only need values, not variable names that are old array indexes
    allowedContributionPageIds = $.map(allowedContributionPageIds, function (value, key) { return value; });
    
    //Activities are looped from end so that rows can be removed with splice()
    var index = ajaxData.aaData.length;
    while(index--) {
      var activity = ajaxData.aaData[index];
      var contributionId = getActivityContributionId(activity);
      
      if(contributionId != null) {
        //Remove Contribution activity if its Contribution page is not allowed to be seen by current user
        var contributionPageId = contributionPageIdForContributionId[contributionId];
        if(allowedContributionPageIds.indexOf(contributionPageId) === -1) {
          ajaxData.aaData.splice(index, 1);
        }
      }
    };
  }
  
  //Add jQuery Ajax prefilter to replace Activity tab default success handler.
  $.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
    if(isActivityTabAjax(options)) {      
      var originalSuccess = options.success;
      options.success = function (data) {
        filterActivityContributions(data);
        originalSuccess(data);
      };
    }
  });

});