# 5.1.0 (in progress)

#### Features

* added test_runner_settings.r_profile_session_path config
* compressed (gzip) session serialization

#### Docker

* updated R to v4.0
* exposed test_runner_settings.r_profile_session_path as env var
* exposed test_runner_settings.r_environ_session_path as env var
* added CONCERTO_R_SERVICES_NUM env var
* added file lock to forker and service guard

## 5.0.28 (in progress)

#### Features

* POST payload can now be read as test input with URL flag
* MySQL 8 support

#### Fixes

* SAML logout process now includes nameId
* fixed *assessment* node *skipped* column of response table being not optional

#### Docker

* Ubuntu 20.04
* PHP 7.4

#### Starter content

* *assessment*'s node *saveResponse* module now accepts *itemSafe* input
* *assessment*'s node post item flow injector now accepts *lastItemsSafe* input

## 5.0.27 (2022-11-04)

#### Features

* _concerto-tick test
* concerto$globalTemplateParams object added
* OTP helpers

#### Docker

* UTC as default timezone for PHP
* can now be installed in subdirectory; base dir will be extracted from CONCERTO_PLATFORM_URL

#### Starter content

* fixedIndex in assessment node now also applied when random order
* random or manual ordered item bank (assessment node) will be now sorted by fixedIndex
* fixed excess template include for item and input components
* assessment node post item flow injector

#### Fixes

* fixed multiple datepickers popping up when data table row has multiple date columns
* fixed data table row update on field blur (even when no change made)
* corrected base template app_url variable name

#### Other

* default session idle timeout changed 1800s -> 300s
* session clear command now uses flock
* checks for R tempdir 
* removed 'session' R package dependency
* fixed 'catR' and 'rjson' R packages version

## 5.0.26 (2021-10-21)

#### Features

* template override
* R session compression
* added json data table column type

#### Fixes

* end test node is now guaranteed to be executed at the end of the test
* added timeout for fifo fopen in session runner service

#### Improvements

* limited max number of messages that are fetched to 100
* limited max number of automatic client side error logs per session to 1
* removed version information from panel login page
* added **debug** option for **concerto.test.run** command

#### Starter content

* exposed **bgWorkers** for **assessment** node template
* added **itemSet** option to **assessment** node

#### Docker

* paths ending with / will be rewritten (nginx)
* prevented routing to /bundles/concertopanel/files/protected (nginx)
* removed **Server** header (nginx)

## 5.0.25 (2021-07-26)

#### Features

* Google Authenticator MFA for panel users
* protected tests

#### Improvements

* updated js dependencies
* removed jquery-migrate dependency
* CONCERTO_KEEP_ALIVE_INTERVAL_TIME default is now 0

#### Starter content

* added **columnPrefix** input to **translationDictionary** node
* gracely scale item type validation requirements now depends on visibility mode 
* params and directives are applied consistently to assessment's item bank

#### Fixes

* disabled default time limits for potentially long running commands
* fixed base template usage when resuming session
* server timer check now uses db times only
* fixed out of sync issue on submission retry
* fixed displaying of null values for date type column
* fixed potential CSTI on login form
* standalone (not forked) run fix
* fixed JWT token TTL
* CKEditor image browser nested path fix

### Docker

* moved default PHP session path out of docker volume
* added CONCERTO_COOKIE_LIFETIME env
* added CONCERTO_SESSION_FORKING env
* /root/env.sh double quote escaping

## 5.0.24 (2021-04-19)

#### Improvements

* test based cookies now expire after 30 days

#### Starter content

* added Item Exclusion Module to **assessment** node
* fixed URL flag value propagation for variables

#### Docker

* UTC is now default timezone for containers

## 5.0.23 (2021-03-25)

#### Fixes

* fixed port default values
* security improvements

## 5.0.22 (2021-02-19)

#### Fixes

* fixed forker guard cron job check
* fixed files permissions when importing content
* client side error logging should now log all js exceptions
* fixed 403 on client side error logging
* fixed 'options' item type fixed index

#### Improvements

* configurable R long running processes forced GC interval (r_forced_gc_interval)
* replaced RSA key pair for JWT with HMAC key (CONCERTO_JWT_SECRET env)
* exposed testRunner.getControlValues()

#### Starter content

* added PATCH method to **http** node
* added createdTime and updateTime column to assessment responses table

#### Docker

* exposed Symfony's config framework.session.save_path as CONCERTO_PHP_SESSION_SAVE_PATH env variable
* import directory is now on shared storage
* /data/php/sessions set as PHP session path
* file lock for forker guard

## 5.0.21 (2021-01-19)

#### Fixes

* **assessment** node now properly calculates total score even when some items are skipped
* **assessment** node options type item now can use value 0
* forker logs are no longer removed by maintenance service
* fixed /files/protected directory permission in docker container

#### Improvements

* forced garbage collection on forker tick
* **assessment** node template params module now also accepts **session** as input
* **assessment** node response saving module now also accepts **templateResponse** as input
* **startSession** node will use database TZ for newly inserted session datetime values
* **finishSession** node will use database TZ for returned session datetime values
* exposed startTestTimer and stopTestTimer js testRunner methods

#### Docker

* forker guard cron job
* forker logs goes to stdout

## 5.0.20 (2020-11-05)

#### Fixes

* fixed false alert about disabled API in administration tab
* fixed API regression caused by 12213b3

#### Other

* exposed js **testRunner.getToken()**

## 5.0.19 (2020-11-03)

#### Features

* home test can now be set from administration tab settings (replaces home page)
* session token is now in session storage instead of cookie

#### Fixes

* import (as new) fix

#### Other

* renamed **CONCERTO_SESSION_COOKIE_EXPIRY_TIME** to **CONCERTO_SESSION_TOKEN_EXPIRY_TIME**
* **assessment** node labels for **options** item type are now centered horizontally and vertically

## 5.0.18 (2020-10-27)

#### Features

* data API can be enabled/disabled by **CONCERTO_DATA_API_ENABLED** setting

#### Fixes

* fixed layout of response options for item type "options"
* js log error action permission fix

## 5.0.17 (2020-10-13)

#### Features

* content import as scheduled task
* content modifications blocked when content modifying scheduled task is ongoing
* added **CONCERTO_COOKIES_SECURE** and **CONCERTO_COOKIES_SAME_SITE** config options exposed through env variables
* added **CONCERTO_KEEP_ALIVE_INTERVAL_TIME**, **CONCERTO_KEEP_ALIVE_TOLERANCE_TIME** and **CONCERTO_SESSION_COOKIE_EXPIRY_TIME** config options exposed through env variables
* can now set column number for **assessment** node 'options' item type
* can now set fixed index for **assessment** node response options ('options' item type)

#### Fixes

* **http** node now handles network errors
* fixed outdated object check
* **assessment** db based resuming fixes
* fixed response option trait scoring in **assessment** node
* flowchart UI fixes
* git permissions fix

#### Other

* AngularJS update to 1.8
* ui-grid update to 4.9

## 5.0.16 (2020-08-28)

#### Features

* protected global files
* protected session files
* JWT token for session ownership and protected global/session file access
* data table column length can be set from panel (length of string, precision and scale for decimal)
* redis integration for session storage
* session logs level

#### Starter content

* added **protectedFilesAccess** and **sessionFilesAccess** as input for **showPage** node
* fixed validation of **assessment** node response with value 0
* restored legacy validation for **assessment** node item types: open, options
* **http** node now also takes status code into consideration when evaluating branch

#### Improvements

* env variables are inherited by R processes

#### Fixes

* fixed server side timer
* no longer possible to modify outdated object, even when the same user is responsible for both modifications
* base template insertion fix

## 5.0.15 (2020-07-30)

#### Fixes

* fixed multiple column grid filters for data table data
* base template import/export fix
* dynamic ports and pointers are now carried over when pasting nodes
* fixed overridable flags for administration settings

#### Improvements

* persistent filters for top objects

## 5.0.14 (2020-07-24)

#### Fixes

* test base view template is now properly included in export
* export fix
* fixed **authorizeUser** node password column mapping
* **assessment** node fixes and improvements (item skipping, aggregated trait scores)

## 5.0.13 (2020-07-09)

#### Features

* base template override for each launchable test

#### Fixes

* import fixes
* renamed SamlToken hash_idx to saml_token_hash_idx for PostgreSQL compatibility
* SAML metadata endpoint fix
* concerto.saml.getAuthenticatedUser fix for serialized session runner service
* reduced memory consumption on collection fetches
* item bank extra fields can now be set for flat item bank table
* removed automatic data connections
* fixed mutlitable dictionary language fallback
* forker won't terminate when empty request read anymore
* most recent data connection is used when multiple data connections attached to input port
* fixed extraFields for flat item banks in assessment node
* fixed flow elements duplication on copy

#### Improvements

* improved import comparison hashing
* sessionHash property exposed in testRunner javascript object
* assessment node fixes/improvements
* concerto objects caching enabled for test session
* more flexible toJSON, fromJSON assignment
* platform_url config setting doesn't have to end with / now
* overridable response.isValid() in each assessment's item type template

#### Starter content

* **assessment** node outputs traitSem and traitTheta
* **assessment** node now keeps track of past responses even when responses are not saved to data table

## 5.0.12 (2020-04-07)

#### Features

* admin panel login brute force prevention

#### Fixes

* fixed R package installation scheduled tasks
* fixed file picker URL
* test error logs are no longer loaded automatically on test edit
* test specific logs always loaded, showing last 100

#### Improvements

* added grandParent helper variable for wizard params hide conditions

#### Starter content

* added slider input type for **form** node
* assessmentItems::skippable column is optional now
* assessmentItems::instructions column is optional now
* assessmentResponses::skipped column is optional now

## 5.0.11 (2020-03-19)

#### Features

* added getTemplate background worker

#### Starter content

* item exposure for **assessment** node
* itemParamsNum no longer required in **assessment** node
* added nullable flags to **assessment** data tables columns
* assessment items can now be made skippable
* pain mannequin no multi mark selection improvements
* extra fields in **assessment** checks if column exist
* **assessment** now returns stopReason
* added stopCheckModule to **assessment**
* skippable items in **assessment** (item level option)
* fallback to default language for dictionary entries with NULL translation
* added extraFields to **finishSession** node
* item level instructions for **assessment**
* bgWorkers exposed as input port in **showPage**
* added **http** node
* hidden scoring sensitive **assessment** data on client side
* added bodyEncode option to **http** node
* added D parameter for **assessment**
* added responseSavedModule to **assessment**
* added itemsAdministered and responses to **assessment** return variables
* added templateResponseModule to **assessment**

#### Improvements

* when importing CSV with empty field - column default value is used
* added connection argument to concerto.table.query and concerto.table.lastInsertId 
* clicking outside of modal in panel no longer closes modal
* increased  data table's data grid columns min width
* added flowIndex argument to c.get and c.set
* replaced posOffset argument with flowIndexOffset in c.get and c.set
* ViewTemplate/{id}/content|html|js|css actions responses are now cacheable
* if there's no dynamic branch with the same name as button pressed on showPage node, use default out node

#### Fixes

* **assessment** MLWI criterions that required scores vector to be passed for nextItem
* **assessment** nextItem's randomesque
* fixed editing of default **form** field
* response scores are not exposed to template when using flat table in **assessment**
* import fix
* better Windows OS detection
* fixed default port values on import

#### Other

* xml2 R package is now required

## 5.0.10 (2020-01-13)

#### Starter content

* pain mannequin item type selection can now be multiple or single per body part
* when setting NULL values in dataManipulation - defaults used
* **showPage** now returns cookies

#### Improvements

* default value for nullable columns is now null

#### Fixes

* data tables can now be included in export when referenced in exported test wizard params
* cookie input validation fix
* fixed setting column as nullable
* fixed test runner API endpoints

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