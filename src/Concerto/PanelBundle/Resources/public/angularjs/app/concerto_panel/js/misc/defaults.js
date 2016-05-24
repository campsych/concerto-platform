function Defaults() {
}
Defaults.ckeditorPanelContentOptions = {
    language: Trans.LANGUAGE,
    filebrowserBrowseUrl: Paths.FILE_UPLOAD_BROWSER,
    filebrowserImageWindowWidth: '1000',
    filebrowserImageWindowHeight: '700',
    extraPlugins: 'cmsource',
    allowedContent: true,
    height: 400,
    contentsCss: [Paths.CSS_PANEL_BUNDLE_1, Paths.CSS_PANEL_BUNDLE_2, Paths.CSS_PANEL_BUNDLE_3, Paths.CSS_PANEL_BUNDLE_4, Paths.CSS_PANEL_BUNDLE_5, Paths.CSS_PANEL_BUNDLE_6, Paths.CSS_PANEL_BUNDLE_7, Paths.CSS_PANEL_BUNDLE_8, Paths.CSS_PANEL_BUNDLE_9, Paths.CSS_PANEL_BUNDLE_10, Paths.CSS_PANEL_BUNDLE_11, Paths.CSS_PANEL_BUNDLE_12, Paths.CSS_PANEL_BUNDLE_13, Paths.CSS_PANEL_BUNDLE_14],
    cmsource: {
        lineWrapping: true,
        lineNumbers: true,
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
        {name: 'document', groups: ['mode', 'document', 'doctools'], items: ['CMSource', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates']},
        {name: 'clipboard', groups: ['clipboard', 'undo'], items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']},
        {name: 'editing', groups: ['find', 'selection', 'spellchecker'], items: ['Find', 'Replace', '-', 'SelectAll', '-', 'Scayt']},
        {name: 'forms', items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField']},
        {name: 'basicstyles', groups: ['basicstyles', 'cleanup'], items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
        {name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi'], items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language']},
        {name: 'links', items: ['Link', 'Unlink', 'Anchor']},
        {name: 'insert', items: ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe']},
        {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
        {name: 'colors', items: ['TextColor', 'BGColor']},
        {name: 'tools', items: ['Maximize', 'ShowBlocks']},
        {name: 'others', items: ['-']},
        {name: 'about', items: ['About']}
    ]
};

Defaults.ckeditorTestContentOptions = {
    language: Trans.LANGUAGE,
    filebrowserBrowseUrl: Paths.FILE_UPLOAD_BROWSER,
    filebrowserImageWindowWidth: '1000',
    filebrowserImageWindowHeight: '700',
    extraPlugins: 'cmsource',
    allowedContent: true,
    height: 400,
    contentsCss: [Paths.CSS_TEST_BUNDLE_1, Paths.CSS_TEST_BUNDLE_2, Paths.CSS_TEST_BUNDLE_3],
    cmsource: {
        lineWrapping: true,
        lineNumbers: true,
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
        {name: 'document', groups: ['mode', 'document', 'doctools'], items: ['CMSource', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates']},
        {name: 'clipboard', groups: ['clipboard', 'undo'], items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']},
        {name: 'editing', groups: ['find', 'selection', 'spellchecker'], items: ['Find', 'Replace', '-', 'SelectAll', '-', 'Scayt']},
        {name: 'forms', items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField']},
        {name: 'basicstyles', groups: ['basicstyles', 'cleanup'], items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
        {name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi'], items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language']},
        {name: 'links', items: ['Link', 'Unlink', 'Anchor']},
        {name: 'insert', items: ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe']},
        {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
        {name: 'colors', items: ['TextColor', 'BGColor']},
        {name: 'tools', items: ['Maximize', 'ShowBlocks']},
        {name: 'others', items: ['-']},
        {name: 'about', items: ['About']}
    ]
};