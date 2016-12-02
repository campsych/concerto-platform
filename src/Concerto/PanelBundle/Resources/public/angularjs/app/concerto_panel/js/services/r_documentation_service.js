'use strict';

concertoPanel.factory('RDocumentation', function ($http, $sce, $timeout, $uibModal) {
    return {
        loaded_html: false,
        loaded_name: false,
        current_timeout: false,
        active: false,
        html: false,
        rCacheDirectory: Paths.R_CACHE_DIRECTORY,
        functionIndex: null,
        showDocumentationHelp: function () {
            $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + "r_documentation_generation_help.html",
                controller: RDocumentationGenerationHelpController,
                resolve: {
                },
                size: "lg"
            });
        },
        autocompletionWizardMapping: {
            'concerto.table.query': {
                template: 'concerto_table_query_wizard_dialog.html',
                controller: ConcertoTableQueryWizardController
            },
            '#default': {
                template: 'default_r_completion_wizard_dialog.html',
                controller: DefaultRCompletionWizardController
            }
        },
        launchWizard: function (widget, replacement, completion, data) {
            var funct_name = this.sanitizeFunctionName(replacement);
            var handler = (this.autocompletionWizardMapping[ funct_name ]) ? this.autocompletionWizardMapping[ funct_name ] :
                    this.autocompletionWizardMapping[ '#default' ];

            var instance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + handler.template,
                controller: handler.controller,
                resolve: {
                    completionWidget: function () {
                        return widget;
                    },
                    selection: function () {
                        return replacement;
                    },
                    completionContext: function () {
                        return completion;
                    },
                    completionData: function () {
                        return data;
                    }
                },
                size: "prc-lg"
            });
            return instance;
        },
        extractHtmlBody: function (data) {
            var html = data.replace(/[\s\S]+(<body)/, "<div");
            return html.replace(/(<\/body>)[\s\S]+/, "</div>");
        },
        extractHtmlDoc: function (data) {
            return $sce.trustAsHtml(this.extractHtmlBody(data));
        },
        sanitizeName: function (name) {
            if (name.substr(name.length - 2) == '()')
                name = name.substr(0, name.length - 2);
            return name;
        },
        getBodyAsHtmlElement: function () {
            var body_html = /*'<div>' + */ this.extractHtmlBody(this.loaded_html)/* + '</div>'*/;
            var res = angular.element(body_html);
            return res;
        },
        getServicePath: function (name) {
            return Paths.R_CACHE_DIRECTORY + "/html/" + name + ".html";
        },
        extractArgumentsFromMetadata: function ( ) {
            var elem = this.getBodyAsHtmlElement();
            var res = new Array();

            var args = angular.fromJson(elem.attr('args'));
            var defs = angular.fromJson(elem.attr('defs'));
            for (var itr = 0; itr < args.length; itr++) {
                var row = new Object();
                row.name = args[ itr ];
                row.default = defs[ itr ];
                res.push(row);
            }
            return res;
        },
        extractArgumentsFromSample: function ( ) {
            var sample = this.getBodyAsHtmlElement().find('pre').first().text();
            sample = sample.replace(/ /g, '').replace(/(\r\n|\n|\r)/gm, ""); // remove whitespace
            // find correct definition
            var matches = sample.match(new RegExp(this.loaded_name + '\\([^\(\)]*?(\\([^\(\)]*?\\))*?[^\(\)]*?\\)', 'g'));
            if (!matches[ 0 ])
                return false;

            // first cut the function name and opening parenthetical
            var definition = matches[ 0 ].substr(this.loaded_name.length + 1);
            var function_arguments = new Array();

            // simply prevent from hanging if someone put rubbish/incorrect syntax in R documentation.
            var hangprev = 100;
            while (hangprev--) {
                var argument = new Object();
                var next_stop = definition.search(/[\)\,\=]{1}/);
                argument.name = definition.substr(0, next_stop);
                definition = definition.substr(next_stop);
                if (definition[ 0 ] == '=') {
                    var tmp_val = definition.match(/\=([^\(\)]*?(\([^\(\)]*?\))*?[^\(\)]*?)[\,\)]{1}/g);
                    if (!tmp_val)
                        return false;
                    else
                        tmp_val = tmp_val[ 0 ];
                    argument.default = tmp_val.substr(1, tmp_val.length - 2);
                    definition = definition.substr(tmp_val.length - 1);
                }
                function_arguments.push(argument);
                if ((definition == "") || (definition[ 0 ] == ')'))
                    break;

                definition = definition.substr(1);
            }

            return function_arguments;
        },
        extractArgumentsFromTable: function (data) {
            var arguments_map = new Object();

            var tables = this.getBodyAsHtmlElement().find('table');

            var table;
            // manually roll to the argblock table, as angular's jqlite may not support selectors...
            for (var itr = 0; itr < tables.length; itr++) {
                table = angular.element(tables[ itr ]);
                var summary = table.attr('summary');

                if (summary && (summary.search('argblock') != -1)) {
                    break;
                } else
                    table = false;
            }
            if (!table)
                return arguments_map;

            var rows = table.find('tr');

            for (var itr = 0; itr < rows.length; itr++) {
                var colls = angular.element(rows[ itr ]).find('td');
                var argname = angular.element(colls[ 0 ]).text().trim();
                var argdesc = angular.element(colls[ 1 ]).text().replace(/(\r\n|\n|\r)/gm, " ").trim();
                if (argname.search('\\,') == -1)
                    arguments_map[ argname ] = argdesc;
                else {
                    var multi_args = argname.split(',');
                    for (var itr2 = 0; itr2 < multi_args.length; itr2++)
                        arguments_map[ multi_args[ itr2 ].trim() ] = argdesc;
                }

            }
            return arguments_map;
        },
        select: function (selected, load_callback) {
            var local_selected_copy = this.sanitizeName(selected);

            // if currently cached version matches what we need, we just return it
            var obj = this;
            if (this.loaded_name == local_selected_copy) {
                $timeout(function () {
                    load_callback(obj.extractHtmlDoc(obj.loaded_html));
                }, 1);
                return;
            }

            this.loaded_name = local_selected_copy;

            if (this.current_timeout) {
                $timeout.cancel(this.current_timeout);
                this.current_timeout = false;
            }

            var obj = this;
            this.current_timeout = $timeout(function () {
                $http.get(obj.getServicePath(local_selected_copy)).success(function (data) {
                    if (data !== null) {
                        load_callback(obj.extractHtmlDoc(data));
                        // we make sure that the name didn't change while loading the HTML
                        if (local_selected_copy == obj.loaded_name)
                            obj.loaded_html = data;
                    }
                });
            }, 50);
        },
        reset: function () {
            this.loaded_html = false;
        },
        apply: function (widget, replacement, completion, data) {
            widget.cm.replaceRange(replacement, completion.from || data.from,
                    completion.to || data.to, "complete");
            widget.cm.execCommand('goCharLeft');
            widget.close();
        },
        sanitizeFunctionName: function (function_name) {
            return this.sanitizeName(function_name);
        },
        getFunctionName: function () {
            return this.sanitizeName(this.loaded_name).trim();
        },
        getTitle: function () {
            return this.getBodyAsHtmlElement().find('h2').first().text();
        },
        getComment: function () {
            return this.getBodyAsHtmlElement().find('p').first().text().replace(/(\r\n|\n|\r)/gm, " ").trim();
        },
        getArguments: function () {
            var html = this.getBodyAsHtmlElement();
            var args_list = this.extractArgumentsFromMetadata(html);
            var args_doc = this.extractArgumentsFromTable(html);

            for (var itr = 0; itr < args_list.length; itr++) {
                if (args_list[ itr ].default)
                    args_list[ itr ].value = args_list[ itr ].default;

                var argname = args_list[ itr ].name;
                if (args_doc[ argname ]) {
                    args_list[ itr ].description = args_doc[ argname ];
                    var tmp = args_doc[ argname ].split('.');
                    args_list[ itr ].comment = tmp[ 0 ];
                }
            }
            return args_list;
        }
    }
});