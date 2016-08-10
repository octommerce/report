var reportLoaded = false;

$(function() {
	$('#report-form').hide();
});

function initReport() {
	if (!reportLoaded) {
		$('#report-form').show().submit();

		$('#report-form select[name="date_range"]').on('change', function() {
	        if ($(this).val() != 'Custom') {
	        	$('#report-form').submit();
	        }
	    });

	    $('#report-form .custom-date-range').on('change', function() {
	        $('#report-form').submit();
	    });

		reportLoaded = true;
	}
}

function loadReport(data) {
    lava.loadData('orders', data.dataTable, function (chart) {
        // console.log(chart);
    });
}
