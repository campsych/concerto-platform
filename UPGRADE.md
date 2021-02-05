## 5.1.0

* renamed test_runner_settings.r_environ_path to test_runner_settings.r_environ_session_path

## 5.0.22

* set value for lexik_jwt_authentication.secret_key (config.yml)

## 5.0.19

* renamed CONCERTO_SESSION_COOKIE_EXPIRY_TIME to CONCERTO_SESSION_TOKEN_EXPIRY_TIME
* requests to GET protected and session files must now include **token** parameter

## 5.0.18

* **CONCERTO_API_ENABLED** setting values changed to [true|false]

## 5.0.13

* js **testRunner.platformUrl** no longer ends with slash character

## 5.0.9

* renamed **cbGroup** column in **CAT** item bank to **trait**
* **assessment** node replaces **CAT** and **linearTest**

## 5.0.8

* **content_import_options** and **content_export_options** config options now merged as **content_transfer_options**
* **content_import_options_overridable** and **content_export_options_overridable** config options now merged as **content_transfer_options_overridable**

## 5.0.7

* concerto$onTemplateSubmit and concerto$onBeforeTemplateShow are removed and should now be declared through **concerto.event.add** function

## 5.0.5

* set **platform_url** configuration parameter (config/paramaters_test_runner.yml or CONCERTO_PLATFORM_URL env variable)
* depracated **concerto.file.getPublicPath**, use **concerto.file.getPath** instead