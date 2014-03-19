/**
* Adds new "Owner Contact Name" field to Contribution admin page
*/
cj(function ($) {
  'use strict';
  
  /**
  * Creates new Owner Contact Id element and makes it a autocomplete element
  */
  var createOwnerElement = function() {
    var html = '<tr id="ownerContactName-tr">' +
        '<td class="label">' + getLabelHTML() + '</td>' +
        '<td>' + getInputHTML() +'</td>' + 
      '</tr>';
    
    //Add element after Financial type dropdown
    $('.crm-contribution-contributionpage-settings-form-block-financial_type_id').after(html);
      
    //Add CiviCRM autocomplete
    $('#ownerContactName').crmAutocomplete({params:{contact_type:'Organization'}});
  };
  
  /**
  * Get HTML for field label for Owner field
  *
  * @return {string} Html for input label
  */
  var getLabelHTML = function() {
    var html = '<label for="ownerContactName">Owner Contact Name</label>' + 
    '<span class="crm-marker" title="This field is required.">*</span>';
    
    if(getError() !== null) {
      html = '<span class="crm-error crm-error-label">' + html + '</span>';
    }
    
    return html;
  };
  
  /**
  * Get html for input element for Owner field
  *
  * @return {string} Html for input element
  */
  var getInputHTML = function() {
    var html = '<input type="text" class="form-text" id="ownerContactName" name="ownerContactName" value="' + CRM.relationshipContributionACL.ownerContactName + '">';
    
    if(getError() !== null) {
      html = html +'<span class="crm-error">' + getError() + '</span>';
    }
    
    return html;
  };
  
  /**
  * Returns possible error message for this field
  *
  * @return {string} Error message for field. Null if no error is found.
  */
  var getError = function() {
    if(CRM.relationshipContributionACL.ownerContactName_emptyErrorMessgage) {
      return CRM.relationshipContributionACL.ownerContactName_emptyErrorMessgage;
    }
    return null;
  };
  
  //Check URL is for creating new Cotribution page
  if(document.URL.indexOf('civicrm/admin/contribute/add') !== -1) {
    //Add view does not contain tabs. Create element just once.
    createOwnerElement();
  }
  //We are not creating new Contribution page but editing a old one so there is tabs
  else {
    //Listen Title-tab load-event and create new Owner element. crmFormLoad event is sent by jQuery tabs-plugin.
    $('#Title').bind('crmFormLoad', function(e){
      createOwnerElement();
    });
  }
});