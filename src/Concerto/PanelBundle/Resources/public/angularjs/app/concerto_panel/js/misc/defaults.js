function Defaults() {
}

Defaults.ckeditorPanelContentOptions = {
    language: Trans.LANGUAGE,
    filebrowserBrowseUrl: Paths.FILE_UPLOAD_BROWSER,
    filebrowserImageWindowWidth: '1000',
    filebrowserImageWindowHeight: '700',
    extraPlugins: 'cmsource,autogrow',
    autoGrow_minHeight: 400,
    allowedContent: true,
    autoParagraph: false,
    fillEmptyBlocks: false,
    htmlEncodeOutput: false,
    basicEntities: false,
    forceSimpleAmpersand: true,
    entities: false,
    protectedSource: [/<src>([\s\S]*?)<\/src>/gi],
    height: 400,
    contentsCss: [Paths.CSS_PANEL_BUNDLE_1, Paths.CSS_PANEL_BUNDLE_2, Paths.CSS_PANEL_BUNDLE_3, Paths.CSS_PANEL_BUNDLE_4, Paths.CSS_PANEL_BUNDLE_5, Paths.CSS_PANEL_BUNDLE_6, Paths.CSS_PANEL_BUNDLE_7, Paths.CSS_PANEL_BUNDLE_9, Paths.CSS_PANEL_BUNDLE_11, Paths.CSS_PANEL_BUNDLE_12, Paths.CSS_PANEL_BUNDLE_13, Paths.CSS_PANEL_BUNDLE_14, Paths.CSS_PANEL_BUNDLE_15],
    cmsource: {
        lineWrapping: true,
        lineNumbers: true,
        indentUnit: 4,
        indentWithTabs: true,
        mode: 'htmlmixed',
        extraKeys: {
            "F11": function (cm) {
                cm.setOption("fullScreen", !cm.getOption("fullScreen"));
            },
            "Esc": function (cm) {
                if (cm.getOption("fullScreen"))
                    cm.setOption("fullScreen", false);
            }
        }
    },
    toolbar: [
        {name: 'document', groups: ['mode'], items: ['CMSource']},
        {
            name: 'clipboard',
            groups: ['clipboard'],
            items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', 'Undo', 'Redo']
        },
        {name: 'editing', groups: ['find'], items: ['Find', 'Replace']},
        {
            name: 'forms',
            items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField']
        },
        {
            name: 'basicstyles',
            groups: ['basicstyles'],
            items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript']
        },
        {
            name: 'paragraph',
            groups: ['list', 'indent', 'blocks', 'align', 'bidi'],
            items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']
        },
        {name: 'links', items: ['Link', 'Unlink', 'Anchor']},
        {name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar', 'PageBreak', 'Iframe']},
        {name: 'styles', items: ['Font', 'FontSize', 'TextColor', 'BGColor', 'RemoveFormat']},
        {name: 'tools', items: ['ShowBlocks', 'Maximize']}
    ],
    fillEmptyBlocks: false
};

Defaults.ckeditorTestContentOptions = {
    language: Trans.LANGUAGE,
    filebrowserBrowseUrl: Paths.FILE_UPLOAD_BROWSER,
    filebrowserImageWindowWidth: '1000',
    filebrowserImageWindowHeight: '700',
    extraPlugins: 'cmsource,autogrow',
    autoGrow_minHeight: 400,
    allowedContent: true,
    autoParagraph: false,
    fillEmptyBlocks: false,
    htmlEncodeOutput: false,
    basicEntities: false,
    forceSimpleAmpersand: true,
    entities: false,
    protectedSource: [/<src>([\s\S]*?)<\/src>/gi],
    height: 400,
    contentsCss: [Paths.CSS_TEST_BUNDLE_1, Paths.CSS_TEST_BUNDLE_2],
    cmsource: {
        lineWrapping: true,
        lineNumbers: true,
        indentUnit: 4,
        indentWithTabs: true,
        mode: 'htmlmixed',
        extraKeys: {
            "F11": function (cm) {
                cm.setOption("fullScreen", !cm.getOption("fullScreen"));
            },
            "Esc": function (cm) {
                if (cm.getOption("fullScreen"))
                    cm.setOption("fullScreen", false);
            }
        }
    },
    toolbar: [
        {name: 'document', groups: ['mode'], items: ['CMSource']},
        {
            name: 'clipboard',
            groups: ['clipboard'],
            items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', 'Undo', 'Redo']
        },
        {name: 'editing', groups: ['find'], items: ['Find', 'Replace']},
        {
            name: 'forms',
            items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField']
        },
        {
            name: 'basicstyles',
            groups: ['basicstyles'],
            items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript']
        },
        {
            name: 'paragraph',
            groups: ['list', 'indent', 'blocks', 'align', 'bidi'],
            items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']
        },
        {name: 'links', items: ['Link', 'Unlink', 'Anchor']},
        {name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar', 'PageBreak', 'Iframe']},
        {name: 'styles', items: ['Font', 'FontSize', 'TextColor', 'BGColor', 'RemoveFormat']},
        {name: 'tools', items: ['ShowBlocks', 'Maximize']}
    ],
    fillEmptyBlocks: false
};