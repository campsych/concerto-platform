CKEDITOR.plugins.add('cmsource', {
    icons: 'cmsource',
    init: function (editor) {
        var _codemirror_instance = false;
        var _editor_wrapper_box = false;
        var _resize_setup = false;
        editor.addMode(
                'cmsource',
                function (callback)
                {
                    callback();
                }
        );

        var handleResize = function () {
            if (_codemirror_instance && !_codemirror_instance.getOption("fullScreen"))
                _codemirror_instance.setSize(_editor_wrapper_box.$.clientWidth - 5, _editor_wrapper_box.$.clientHeight);
        };

        var _data_transfer_running = false;

        var _data_return_running = false;

        var setupEditor = function (plugin, editor)
        {
            var contentsSpace = editor.ui.space('contents');
            var source_editor = contentsSpace.getDocument().createElement('textarea');

            var _data_return_running = true;
            var text_content = editor.getData();
            var _data_return_running = false;

            source_editor.setStyles(
                    CKEDITOR.tools.extend({
                        width: CKEDITOR.env.ie7Compat ? '99%' : '100%',
                        height: '100%',
                        resize: 'none',
                        outline: 'none',
                        'text-align': 'left',
                        display: 'block',
                        'z-index': 100,
                        background: 'black'
                    }));


            var $injector = angular.injector(['ng', 'ui.codemirror']);
            $injector.invoke(function ($rootScope, $compile)
            {
                source_editor.setAttributes(
                        {
                            'ui-codemirror': '{ onLoad : codemirrorLoaded }',
                            'ui-codemirror-opts': 'cmOptions'
                        }
                );

                contentsSpace.append(source_editor);
                _editor_wrapper_box = source_editor.getParent();

                var cm_width = parseInt(source_editor.getParent().$.clientWidth);
                var cm_height = parseInt(source_editor.getParent().$.clientHeight);

                if (editor.config.cmsource)
                {
                    $rootScope.cmOptions = editor.config.cmsource;
                } else // sane defaults
                {
                    $rootScope.cmOptions = {
                        lineWrapping: true,
                        lineNumbers: true,
                        mode: 'htmlmixed', // 'sql' 'r'
                    };
                }

                $rootScope.codemirrorLoaded = function (_editor) {
                    _editor.focus();
                    _editor.setSize(cm_width, cm_height);
                    _editor.setValue(text_content);
                    _codemirror_instance = _editor;

                    _editor.on("change",
                            function (_editor, change) {
                                _data_transfer_running = true;
                                editor.setData(_editor.getValue());
                                editor.fire('change');
                                _data_transfer_running = false;
                            }
                    );
                };

                source_editor.$ = $compile(source_editor.$)($rootScope);
            }

            );

            win = CKEDITOR.document.getWindow();

            if (!_resize_setup)
            {
                _resize_setup = true;
                editor.on('resize', handleResize);
                CKEDITOR.document.getWindow().on('resize', handleResize);
            }
        }

        var switchEditor = function (editor) {
            //                 editor.getCommand( 'cmsource' ).setState( CKEDITOR.TRISTATE_ON );
            editor.setMode(editor.mode == 'cmsource' ? 'wysiwyg' : 'cmsource');

            if (!(_codemirror_instance))
                setupEditor(this, editor);

            if (editor.mode == 'cmsource')
            {
                try
                {
                    _data_return_running = true;
                    _codemirror_instance.setValue(editor.getData());
                    _data_return_running = false;
                } catch (exc)
                {
                    // we're fine here, it'll be loaded by default then
                }
            } else
            {
                _data_transfer_running = true;
                editor.setData(_codemirror_instance.getValue());
                editor.fire('change');
                _data_transfer_running = false;
                _codemirror_instance = false;
            }
        }

        var handleSetData = function (event, passed_editor) {
            if (_data_transfer_running)
                return;
            if (editor.mode == 'cmsource')
                switchEditor(editor);
        }


        editor.addCommand('cmsource', {
            modes: {wysiwyg: 1, cmsource: 1},
            exec: function (editor) {
                switchEditor(editor);

            }
        }
        );

        editor.ui.addButton('CMSource', {
            label: 'CMSource',
            command: 'cmsource',
            toolbar: 'insert',
            modes: {wysiwyg: 1, cmsource: 1},
            readOnly: 1,
        });

        editor.on('afterSetData', handleSetData);

    },
}
);
