## 5.0.4 (work in progress)

#### Features

* added **CONCERTO_CONTENT_IMPORT_AT_START** env var

#### Improvements

* more diff friendly content export
* limited number of git history entries returned

#### Fixes

* fixed data import warnings
* public files permissions are now set at correct time in Dockerfile
* fixed test node port update on test variable import
* when adding native port to nodes that already contains dynamic port named the same, dynamic port will be converted to native
* fixed not persisted entities found in relations on import
* added missing git dependencies

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