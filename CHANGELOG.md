## 5.0.6 (work in progress)

#### Improvements

* improved logging for forker and forked child processes
* test variables sorted by name for prettier export diffs
* added working path writeable check to health check

#### Fixes

* fixed form select element values

## 5.0.5 (2019-11-02)

#### Features

* SAML flow API (experimental)
* cookies are now included in concerto.template.show return (**result$.cookies**) for both submit and background worker actions
* concerto.saml.getAuthenticatedUser R method
* session resume endpoint
* **concerto.template.show** now accepts **cookies** input parameter (name-value pair list)
* added **CONCERTO_BEHIND_PROXY** env and **behind_proxy** config option for SAML toolkit

#### Improvements

* Git requests more verbose
* Git pull command will try to do non fast-forward merge automatically if possible 

#### Starter content

* added **instructions** block to **test** and **form** template
* added **buttonLabel** for **form** and **page** template
* added **nextButtonLabel** and **backButtonLabel** for **test** template
* default templates improvements

#### Fixes

* Disabled escape characters for both CSV import and export
* fixed unforked session initialization
* fixed **showPage** node
* client side template params object is now cleared each time template is shown (testRunner.R)

#### Other

* added **platform_url** config parameter
* renamed **concerto.file.getPublicPath** to **concerto.file.getPath**
* added **concerto.saml.login** R function
* added **concerto.saml.logout** R function
* added **concerto.session.getResumeUrl** R function
* added **concerto.template.redirect** R function

## 5.0.4 (2019-10-21)

#### Features

* added **CONCERTO_CONTENT_IMPORT_AT_START** env var

#### Improvements

* more diff friendly content export
* limited number of git history entries returned

#### Starter content

* **CAT** now returns **testTimeLeft**
* added dynamic inputs to **settings** of **CAT** and **linearTest**
* **settings** are now passed as input parameter to all **CAT** and **linearTest** modules
* added number and date input type for **form**

#### Fixes

* fixed data import warnings
* public files permissions are now set at correct time in Dockerfile
* fixed test node port update on test variable import
* when adding native port to nodes that already contains dynamic port named the same, dynamic port will be converted to native
* fixed not persisted entities found in relations on import
* added missing git dependencies
* no response bank fix for **CAT** and **linearTest**
* fixed "go back" button on **linearTest** 

## 5.0.3 (2019-09-27)

#### Fixes

* fixed checkbox form item type
* fixed pain mannequin test item type

## 5.0.2 (2019-09-18)

#### Fixes

* fixed reading authorized user info for non super admin users
* prevented unnecessary collections fetch calls for unauthorized resources when non super admin user
* **test** template form element now has autocomplete attribute set to off  
* fixed duplicate draw calls for nodes on Firefox

## 5.0.1 (2019-09-09)

#### Starter content
* added **log** node
* added wizard for **eval** node
* **showPage** now has native **.branch** return port and will use **buttonPressed** value for **.branch** when at least one dynamic branch is created
* added **insertId** return variable to **dataManipulation** node

#### Fixes
* fixed disappearing values on some wizard params
* fixed complex type wizard param's nesting
* fixed **linearTest** custom item table
* fixed setting null data table wizard param related values to recently edited data table
* prefixed local variables in **_dataManipulation** to prevent name overlaps with dynamic inputs
* fixed data manipulation wizard descriptions and param's hide rules