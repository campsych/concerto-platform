## 5.0.8

* **content_import_options** and **content_export_options** config options now merged as **content_transfer_options**
* **content_import_options_overridable** and **content_export_options_overridable** config options now merged as **content_transfer_options_overridable**

## 5.0.7

* concerto$onTemplateSubmit and concerto$onBeforeTemplateShow are removed and should now be declared through **concerto.event.add** function

## 5.0.5

* set **platform_url** configuration parameter (config/paramaters_test_runner.yml or CONCERTO_PLATFORM_URL env variable)
* depracated **concerto.file.getPublicPath**, use **concerto.file.getPath** instead