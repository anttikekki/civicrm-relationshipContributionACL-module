civicrm-relationshipContributionACL-module
==========================================
[CiviCRM] (https://civicrm.org/) module to use contact relationships edit rights to determine Contribution page visibility and editability in CiviCRM administration screens. This module adds new field to Contribution admin screen to store owner information.

This module uses relationships instead of groups or ACL to limit visibility and editability. The whole relationship tree is searched and all Contribution pages that are owned by contacts to where user has edit permissions through relationships are made visible and editable. All contact types are searched.

Portions of this module is based on the idea of [Relationship Permissions as ACLs] (https://civicrm.org/extensions/relationship-permissions-acls) extension. This module includes code from [relationshipACL](https://github.com/anttikekki/civicrm-relationshipACL-module) module.

### Example
* Organisation 1
* Sub-organisation 1 (Organisation 1 has edit relationship to this organisation)
* Sub-organisation 2 (Sub-organisation 1 has edit relationship to this organisation)
* User 1 (has edit relationship to Sub-organisation 1)

Contribution pages
* Contribution page 1. Owned by Organisation 1.
* Contribution page 2. Owned by Sub-organisation 2.

With this module User 1 can see and edit Contribution page 2 but not Contribution page 1. Contribution page 2 is owned by Sub-organisation 2 that User 1 has edit rights. User 1 does not have edit rights to Organisation 1 so this Contribution page is invisible to user.

### Installation
Create _com.github.anttikekki.relationshipContributionACL_ folder to CiviCRM extension folder and copy all files into it. Install and enable extension in administration.

This module uses temporary tables in database so CiviCRM MySQL user has to have permissions to create these kind of tables.

### Performance considerations
This module performance on large CiviCRM sites may be poor. Module needs to determine the relationship tree structure on every administration Manage Contribution page pageload. The relationship tree may be deep and complex. This means 1 query for every relationship level. The search done with help of temporary table in database.

This logic may lead to multiple large queries and large temporary table on every event Manage Contribution page load in contact administration.

### Licence
GNU Affero General Public License
