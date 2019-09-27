## 5.1.0 (work in progress)

#### Features

* SAML flow API
* cookies are now included in concerto.template.show return (**result$.cookies**) for both submit and background worker actions
* concerto.saml.getAuthenticatedUser R method
* session resume endpoint

#### Fixes

* fixed unforked session initialization

#### Other

* added **platform_url** config parameter
* renamed **concerto.file.getPublicPath** to **concerto.file.getPath**
* added **concerto.saml.login** R function
* added **concerto.saml.logout** R function
* added **concerto.session.getResumeUrl** R function
* added **concerto.template.redirect** R function

## 5.0.3 (work in progress)

#### Fixes

* fixed checkbox form item type

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