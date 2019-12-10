concertoPanel.factory('GridService', function ($uibModal, DialogsService) {
    CSV.error = function (err) {
        let msg = CSV.dump(err);
        CSV.reset();

        DialogsService.alertDialog(
            Trans.DIALOG_TITLE_CSV,
            msg,
            "danger"
        );
    };

    return {
        downloadList: function (gridApi) {
            let modalInstance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'download_list_dialog.html',
                controller: DownloadListController,
                size: "lg"
            });
            modalInstance.result.then(function (options) {
                if (options.format === 'csv') {
                    let elem = angular.element(document.querySelectorAll(".custom-csv-link-location"));
                    gridApi.exporter.csvExport(options.rows, options.cols, elem);
                } else if (options.format === 'pdf') {
                    gridApi.exporter.pdfExport(options.rows, options.cols);
                }
            });
        },
        uploadList: function (gridApi) {
            let modalInstance = $uibModal.open({
                templateUrl: Paths.DIALOG_TEMPLATE_ROOT + 'upload_list_dialog.html',
                controller: UploadListController,
                size: "lg"
            });
            modalInstance.result.then(function (item) {
                gridApi.importer.importFile(item._file);
            });
        }
    };
});