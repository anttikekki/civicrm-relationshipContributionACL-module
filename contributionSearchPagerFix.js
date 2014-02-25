/**
* Alter 'Find participants' search form to include 'limit=500' parameter in form target url. This 
* sets search result row limit to 500. This is needed because increase pager page size.
*/
cj(function ($) {
  'use strict';
  
  var form = $('#Search');
  var action = form.attr('action');
  var noURLParameters = action.indexOf('?') === -1;
  action = action + (noURLParameters ? '?' : '&') + 'limit=500';
  form.attr('action', action);

});