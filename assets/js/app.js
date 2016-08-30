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

$(document).ready(function() {
    $('#report-form-interval input:radio[name="interval"]').on('change', function() {
        $('#report-form-interval').request('onLoadByInterval', {
            data: {
                date_range: $('#report-form select[name="date_range"]').val(),
                start_date: $('#report-form input[name="start_date"]').val(),
                end_date: $('#report-form input[name="end_date"]').val(),
                type: $('#report-form-type .active input:radio[name="type"]').val()
            },
            success: function(data) {
                loadReport(data); 
            }
        });
    });

    $('#report-form-type input:radio[name="type"]').on('change', function() {
        $('#report-form-type').request('onLoadByInterval', {
            data: {
                date_range: $('#report-form select[name="date_range"]').val(),
                start_date: $('#report-form input[name="start_date"]').val(),
                end_date: $('#report-form input[name="end_date"]').val(),
                interval: $('#report-form-interval .active input:radio[name="interval"]').val()
            },
            success: function(data) {
                loadReport(data); 
            }
        });
    });

    $("#report-form-interval .btn-default").click(function(){
        var 
        elements = $("#report-form-interval .btn-default");
        elements.removeClass("active");
        $(this).addClass("active");
    }); 

    $("#report-form-type .btn-default").click(function(){
        var 
        elements = $("#report-form-type .btn-default");
        elements.removeClass("active");
        $(this).addClass("active");
    }); 
});

function loadReport(data) {
    lava.loadData('orders', data.dataTable, function (chart) {
        // console.log(chart);
    });
}

