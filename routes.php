<?php

Route::group(['prefix' => 'api/octommerce/report', 'namespace' => 'Octommerce\Report\Http'], function() {
    /* Route::get('data', 'ReportController@getData'); */
});

Route::get('octommerce/report/download', ['as' => 'report.download', function() {
    return \Octommerce\Report\Classes\Export::report()->excel()->download();
}]);
