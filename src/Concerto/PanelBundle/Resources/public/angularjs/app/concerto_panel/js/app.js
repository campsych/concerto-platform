var concertoPanel = angular.module('concertoPanel', [
    'ngAnimate',
    'ui.bootstrap',
    'ui.grid',
    'ui.grid.pagination',
    'ui.grid.autoResize',
    'ui.grid.edit',
    'ui.grid.cellNav',
    'ui.grid.resizeColumns',
    'ui.grid.exporter',
    'ui.grid.selection',
    'ui.grid.moveColumns',
    'ui.grid.importer',
    'angularFileUpload',
    'blockUI',
    'ui.codemirror',
    'ngSanitize',
    'ui.sortable',
    'ui.router',
    'ncy-angular-breadcrumb',
    "ng-html",
    'ng-context-menu',
    "chart.js",
    "FileManagerApp",
    "ja.qr"
]);

concertoPanel.config(function ($interpolateProvider) {
    //$interpolateProvider.startSymbol('//');
    //$interpolateProvider.endSymbol('//');
}).config(function (blockUIConfig) {

    blockUIConfig.requestFilter = function (config) {

        if (config.url.match(/^.*\/rcache\/?\/html\/.*/)) return false;
        if (config.url.match(/^.*\/admin\/DataTable\/\d+\/row\/\d+\/update/)) return false;
        if (config.url.match(/^.*\/admin\/Administration\/ScheduledTask\/collection/)) return false;
    };
    blockUIConfig.message = Trans.PLEASE_WAIT;
    blockUIConfig.delay = 250;
}).config(function ($httpProvider) {
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
    var param = function (obj) {
        var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

        for (name in obj) {
            value = obj[name];

            if (value instanceof Array) {
                for (i = 0; i < value.length; ++i) {
                    subValue = value[i];
                    fullSubName = name + '[' + i + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += param(innerObj) + '&';
                }
            } else if (value instanceof Object) {
                for (subName in value) {
                    subValue = value[subName];
                    fullSubName = name + '[' + subName + ']';
                    innerObj = {};
                    innerObj[fullSubName] = subValue;
                    query += param(innerObj) + '&';
                }
            } else if (value !== undefined && value !== null)
                query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
        }

        return query.length ? query.substr(0, query.length - 1) : query;
    };

    $httpProvider.defaults.transformRequest = [
        function (data) {
            return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
        }
    ];
}).config(function ($breadcrumbProvider) {
    $breadcrumbProvider.setOptions({
        templateUrl: Paths.BREADCRUMBS_TEMPLATE
    });
}).config(function ($stateProvider, $urlRouterProvider, $locationProvider) {
    $urlRouterProvider.otherwise('/tests');

    $stateProvider
        .state('tests', {
            url: '/tests',
            views: {
                "tabViewTest": {}
            },
            ncyBreadcrumb: {
                label: Trans.TEST_BREADCRUMB_LIST
            }
        })
        .state('testsForm', {
            url: '/tests/{id}',
            views: {
                "tabViewTest": {}
            },
            ncyBreadcrumb: {
                label: '#{{object.id}}: {{object.name}}',
                parent: 'tests'
            }
        })
        .state('templates', {
            url: '/templates',
            views: {
                "tabViewViewTemplate": {}
            },
            ncyBreadcrumb: {
                label: Trans.VIEW_TEMPLATE_BREADCRUMB_LIST
            }
        })
        .state('templatesForm', {
            url: '/templates/{id}',
            views: {
                "tabViewViewTemplate": {}
            },
            ncyBreadcrumb: {
                label: '#{{object.id}}: {{object.name}}',
                parent: 'templates'
            }
        })
        .state('tables', {
            url: '/tables',
            views: {
                "tabViewDataTable": {}
            },
            ncyBreadcrumb: {
                label: Trans.DATA_TABLE_BREADCRUMB_LIST
            }
        })
        .state('tablesForm', {
            url: '/tables/{id}',
            views: {
                "tabViewDataTable": {}
            },
            ncyBreadcrumb: {
                label: '#{{object.id}}: {{object.name}}',
                parent: 'tables'
            }
        })
        .state('files', {
            url: '/files',
            views: {
                "tabViewFile": {}
            },
            ncyBreadcrumb: {
                label: Trans.FILE_BROWSER_BREADCRUMB_FILES
            }
        })
        .state('users', {
            url: '/users',
            views: {
                "tabViewUser": {}
            },
            ncyBreadcrumb: {
                label: Trans.USER_BREADCRUMB_LIST
            }
        })
        .state('usersForm', {
            url: '/users/{id}',
            views: {
                "tabViewUser": {}
            },
            ncyBreadcrumb: {
                label: '#{{object.id}}: {{object.username}}',
                parent: 'users'
            }
        })
        .state('wizards', {
            url: '/wizards',
            views: {
                "tabViewTestWizard": {}
            },
            ncyBreadcrumb: {
                label: Trans.TEST_WIZARD_BREADCRUMB_LIST
            }
        })
        .state('wizardsForm', {
            url: '/wizards/{id}',
            views: {
                "tabViewTestWizard": {}
            },
            ncyBreadcrumb: {
                label: '#{{object.id}}: {{object.name}}',
                parent: 'wizards'
            }
        })
        .state('administration', {
            url: '/administration',
            views: {
                "tabViewAdministration": {}
            },
            ncyBreadcrumb: {
                label: Trans.ADMINISTRATION_BREADCRUMB
            }
        });
}).config(function ($uibTooltipProvider) {
    $uibTooltipProvider.options({
        "placement": "auto top",
        "appendToBody": true
    });
}).config(function ($uibModalProvider) {
    $uibModalProvider.options.backdrop = 'static';
    $uibModalProvider.options.keyboard = true;
});

angular.module('FileManagerApp').config(["fileManagerConfigProvider", function (fileManagerConfigProvider) {

    var url = new URL(location.href);
    var CKEditorFuncNum = url.searchParams.get("CKEditorFuncNum");

    fileManagerConfigProvider.set({
        appName: 'Concerto Platform',
        defaultLang: Trans.LANGUAGE,
        multiLang: false,

        listUrl: Paths.FILE_LIST,
        uploadUrl: Paths.FILE_UPLOAD,
        renameUrl: Paths.FILE_RENAME,
        copyUrl: Paths.FILE_COPY,
        moveUrl: Paths.FILE_MOVE,
        removeUrl: Paths.FILE_DELETE,
        editUrl: Paths.FILE_EDIT,
        getContentUrl: Paths.FILE_CONTENT,
        createFolderUrl: Paths.FILE_CREATE_DIRECTORY,
        downloadFileUrl: Paths.FILE_DOWNLOAD,
        downloadMultipleUrl: Paths.FILE_DOWNLOAD_MULTIPLE,
        compressUrl: Paths.FILE_COMPRESS,
        extractUrl: Paths.FILE_EXTRACT,
        permissionsUrl: Paths.FILE_PERMISSIONS,
        basePath: '/',

        allowedActions: {
            upload: true,
            rename: true,
            move: true,
            copy: true,
            edit: true,
            changePermissions: true,
            compress: true,
            compressChooseName: true,
            extract: true,
            download: true,
            downloadMultiple: true,
            preview: true,
            remove: true,
            createFolder: true,
            pickFiles: CKEditorFuncNum !== null,
            pickFolders: false
        },

        multipleDownloadFileName: 'concerto-files.zip',
        showExtensionIcons: true,
        isEditableFilePattern: /\.(csv|txt|diff?|patch|svg|asc|cnf|cfg|conf|html?|.html|cfm|cgi|aspx?|ini|pl|py|md|css|cs|js|jsp|log|htaccess|htpasswd|gitignore|gitattributes|env|json|atom|eml|rss|markdown|sql|xml|xslt?|sh|rb|as|bat|cmd|cob|for|ftn|frm|frx|inc|lisp|scm|coffee|php[3-6]?|java|c|cbl|go|h|scala|vb|tmpl|lock|go|yml|yaml|tsv|lst)$/i,
        //tplPath: '/bundles/concertopanel/js/angular-filemanager/templates',

        pickCallback: function (item) {
            let url = "/files" + item.fullPath();
            window.opener.CKEDITOR.tools.callFunction(CKEditorFuncNum, url);
            window.close();
        }

    });

}]);

//fix for initial state on page load
concertoPanel.run(['$state', function ($state) {
}]);

jsPlumb.importDefaults({
    Connector: ["Straight", {stub: 30}]
});