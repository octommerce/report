<?php

Route::group(['prefix' => 'api/octommerce/report', 'namespace' => 'Octommerce\Report\Http'], function() {
    Route::get('data', 'ReportController@getData');
});
