/**
* Adds new "Owner Contact Name" field to Contribution admin page
*/
cj(function ($) {
  'use strict';
  
  //Creates new Owner Contact Id element and makes it a autocomplete element
  var createOwnerElementFn = function() {
    $('.crm-contribution-contributionpage-settings-form-block-financial_type_id').after(
      '<tr>' +
      '<td class="label"><label for="ownerContactName">Owner Contact Name</label></td>' +
      '<td><input type="text" class="form-text" id="ownerContactName" name="ownerContactName" value="' + CRM.relationshipContributionACL.ownerContactName + '"></td>' + 
      '</tr>');
    $('#ownerContactName').crmAutocomplete({params:{contact_type:'Organization'}});
  };
  
  //From http://stackoverflow.com/a/901144
  var getURLParameterByNameFn = function(name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
};
  
  if(getURLParameterByNameFn('q') == 'civicrm/admin/contribute/add') {
    //Add view does not contain tabs. Create element just once.
    createOwnerElementFn();
  }
  else {
    //Listen Title-tab load-event and create new Owner element. crmFormLoad event is sent by jQuery tabs-plugin.
    $('#Title').bind('crmFormLoad', function(e){
      createOwnerElementFn();
    });
  }
});