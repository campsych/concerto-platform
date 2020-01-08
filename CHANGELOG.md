## 5.0.10 (in progress)

#### Starter content

* pain mannequin item type selection can now be multiple or single per body part
* when setting NULL values in dataManipulation - defaults used

#### Improvements

* default value for nullable columns is now null

#### Fixes

* data tables can now be included in export when referenced in exported test wizard params

## 5.0.9 (2020-01-07)

#### Features

* data table columns can now be nullable

#### Fixes

* removed redundant data table's data calls
* data table's data pagination is now properly reset when changing tables
* data tables can now scroll horizontally
* cell editing focus loss fixed
* fixed list download/upload on column map definer
* fixed ports value for NULLs

#### Starter content

* added **scoring** node
* added **assessment** node (replaces **CAT** and **linearTest**)
* added **saveData** node
* added **finishSession** node
* added minAccuracyMinItems stopping rule to **CAT**
* execution no longer is halted when can't satisfy CB rules in **CAT**
* added more descriptive error message when parsing responseOptions JSON fails in **CAT**
* renamed **cbGroup** column in **CAT** item bank to **trait**
* default template inserts exposed as wizard params in **CAT**, **form** and **showPage**
* can now use use dynamic return ports to return values of input ports in **form** and **assessment**

#### Improvements

* added SHOW statement to **concerto.table.query**
* html argument takes precedence over templateId in **concerto.template.join**
* data tables can now be scrolled horizontally
* removed alert on successful object save
* flow variable pointer name is visible on port label when its value is different than port variable name
* ports receiving value from data connection can now use default value if passed value is NULL

## 5.0.8 (2019-12-03)

#### Features

* potentially long running Git actions are now made as a scheduled task no longer limited by web request time
* configurable Git repository path
* view template's content endpoints

#### Starter content

* **CAT** response options can now be left blank if custom mechanism used
* pain mannequin item response two-way binding (js)
  
#### Improvements

* unified URL content and Git content sections
* **content_import_options** and **content_export_options** config options now merged as **content_transfer_options**
* **content_import_options_overridable** and **content_export_options_overridable** config options now merged as **content_transfer_options_overridable**
* flow node size is adjusted to its contents


#### Fixes

* importing CSV to grid with invalid structure now properly sets missing fields to their type default value

## 5.0.7 (2019-11-20)

#### Starter content

* database based resuming for **CAT**
* session state restore is now optional when resuming session in **startSession**
* added parallel session usage prevention option to **startSession**

#### Features

* added **concerto.event.add** function to R package
* added **concerto.event.fire** function to R package
* added **concerto.event.remove** function to R package 

#### Fixes

* fixed possible two requests for same session running at once when forking is used on multi-instance setup

#### Improvements

* setting **session_files_expiration** config to negative value will disable session clearing

## 5.0.6 (2019-11-11)

#### Improvements

* improved logging for forker and forked child processes
* test variables sorted by name for prettier export diffs
* added working path writeable check to health check

#### Fixes

* fixed form select element values
* fixed global variables scope on resuming
* fixed standalone session runner missing use statement

#### Starter content

* added **Test Time Limit Type** option to **CAT**

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