'use strict';

concertoPanel.service('RDocumentation', ['$http', '$sce', '$timeout',
    function ($http, $sce, $timeout) {

        var loaded_html = false;
        var loaded_name = false;

        var current_timeout = false;

        var extractHtmlBody = function (data) {
            var html = data.replace(/[\s\S]+(<body)/, "<div");
            return html.replace(/(<\/body>)[\s\S]+/, "</div>");
        }

        var extractHtmlDoc = function (data) {
            return $sce.trustAsHtml(extractHtmlBody(data));
        }

        var sanitizeName = function (name) {
            if (name.substr(name.length - 2) == '()')
                name = name.substr(0, name.length - 2);
            return name;
        }

        var getBodyAsHtmlElement = function () {

            var body_html = /*'<div>' + */ extractHtmlBody(loaded_html)/* + '</div>'*/;
            var res = angular.element(body_html);
//             console.log( res );
            return res;

        }

        var getServicePath = function (name) {
            return Paths.R_CACHE_DIRECTORY + "/html/" + name + ".html";
        }

        var extractArgumentsFromMetadata = function ( ) {
            var elem = getBodyAsHtmlElement();
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
        }

        var extractArgumentsFromSample = function ( ) {
            var sample = getBodyAsHtmlElement().find('pre').first().text();
            sample = sample.replace(/ /g, '').replace(/(\r\n|\n|\r)/gm, ""); // remove whitespace
            // find correct definition
            var matches = sample.match(new RegExp(loaded_name + '\\([^\(\)]*?(\\([^\(\)]*?\\))*?[^\(\)]*?\\)', 'g'));
            if (!matches[ 0 ])
                return false;

            // first cut the function name and opening parenthetical
            var definition = matches[ 0 ].substr(loaded_name.length + 1);
            var function_arguments = new Array();

            // simply prevent from hanging if someone put rubbish/incorrect syntax in R documentation.
            var hangprev = 100;
            while (hangprev--) {
                // console.log( 'ITER' );
                // console.log( function_arguments );

                // console.log( definition );
                var argument = new Object();
                var next_stop = definition.search(/[\)\,\=]{1}/);
                argument.name = definition.substr(0, next_stop);

                // console.log( 'ARG' );
                // console.log( argument.name );
                definition = definition.substr(next_stop);

                // console.log( definition );
                if (definition[ 0 ] == '=') {
                    // console.log( 'DEF' );

                    var tmp_val = definition.match(/\=([^\(\)]*?(\([^\(\)]*?\))*?[^\(\)]*?)[\,\)]{1}/g);
                    if (!tmp_val)
                        return false;
                    else
                        tmp_val = tmp_val[ 0 ];
                    argument.default = tmp_val.substr(1, tmp_val.length - 2);

                    // console.log( argument.default );
                    definition = definition.substr(tmp_val.length - 1);
                    // console.log( definition );

                }
                function_arguments.push(argument);
                if ((definition == "") || (definition[ 0 ] == ')'))
                    break;

                definition = definition.substr(1);
            }

            return function_arguments;
        }

        var extractArgumentsFromTable = function (data) {
            var arguments_map = new Object();

            var tables = getBodyAsHtmlElement().find('table');

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
        }

        this.select = function (selected, load_callback) {
            var local_selected_copy = sanitizeName(selected);

            // if currently cached version matches what we need, we just return it
            if (loaded_name == local_selected_copy) {
                $timeout(function () {
                    load_callback(extractHtmlDoc(loaded_html));
                }, 1);
                return;
            }

            loaded_name = local_selected_copy;

            if (current_timeout) {
                $timeout.cancel(current_timeout);
                current_timeout = false;
            }

            current_timeout = $timeout(function () {
                $http.get(getServicePath(local_selected_copy)).success(function (data) {
                    if (data !== null) {
                        load_callback(extractHtmlDoc(data));
                        // we make sure that the name didn't change while loading the HTML
                        if (local_selected_copy == loaded_name)
                            loaded_html = data;
                    }
                });
            },
                    50
                    );
        };

        this.reset = function () {
            loaded_html = false;
        }

        this.apply = function (widget, replacement, completion, data) {
            widget.cm.replaceRange(replacement, completion.from || data.from,
                    completion.to || data.to, "complete");
            widget.cm.execCommand('goCharLeft');
            widget.close();
        }

        this.sanitizeFunctionName = function (function_name) {
            return sanitizeName(function_name);
        }

        this.getFunctionName = function () {
            return sanitizeName(loaded_name).trim();
        }

        this.getTitle = function () {
            return getBodyAsHtmlElement().find('h2').first().text();
        }


        this.getComment = function () {
            return getBodyAsHtmlElement().find('p').first().text().replace(/(\r\n|\n|\r)/gm, " ").trim();
        }

        this.getArguments = function () {
            var html = getBodyAsHtmlElement();
            var args_list = extractArgumentsFromMetadata(html);
            var args_doc = extractArgumentsFromTable(html);

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
]);