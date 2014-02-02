cj(function ($) {
  'use strict';
  
  //Inject new element to form with current value
  setTimeout(function(){
    $('.crm-contribution-contributionpage-settings-form-block-financial_type_id').after(
      '<tr>' +
      '<td class="label"><label for="ownerContactName">Owner Contact Id</label></td>' +
      '<td><input type="text" class="form-text" id="ownerContactName" name="ownerContactName" value="' + CRM.relationshipContributionACL.ownerContactName + '"></td>' + 
      '</tr>');
    $('#ownerContactName').crmAutocomplete({params:{contact_type:'Organization'}});
  },1000);
});