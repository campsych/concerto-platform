var concertoPanel = angular.module('concertoPanel', ['ngAnimate', 'ui.bootstrap', 'ui.grid', 'ui.grid.pagination', 'ui.grid.autoResize', 'ui.grid.edit', 'ui.grid.cellNav', 'ui.grid.resizeColumns', 'ui.grid.exporter', 'ui.grid.selection', 'ui.grid.moveColumns', 'ui.grid.importer', 'angularFileUpload', 'blockUI', 'ui.codemirror', 'ngSanitize',
  'ui.sortable', 'ui.router', 'ncy-angular-breadcrumb', "angular-bind-html-compile", 'ng-context-menu', "chart.js"]);

concertoPanel.config(function ($interpolateProvider) {
  //$interpolateProvider.startSymbol('//');
  //$interpolateProvider.endSymbol('//');
}).config(function (blockUIConfig) {

  blockUIConfig.requestFilter = function (config) {

    if (config.url.match(/^.*\/rcache\/?\/html\/.*/)) {
      return false;
    }
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
          label: Trans.FILE_BROWSER_BREADCRUMB_MANAGER
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
});

concertoPanel.config(function ($uibTooltipProvider) {
  $uibTooltipProvider.options({
    "placement": "auto top",
    "appendToBody": true
  });
});

//fix for initial state on page load
concertoPanel.run(['$state', function ($state) {
}]);

jsPlumb.importDefaults({
  Connector: ["Straight", {stub: 30}]
});

$.each(CKEDITOR.dtd.$removeEmpty, function (i, value) {
  CKEDITOR.dtd.$removeEmpty[i] = false;
});
CKEDITOR.dtd.$removeEmpty.div = false;