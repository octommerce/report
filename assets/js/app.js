$(document).ready(function () {
    $('select[name="date_range"]').on('change', function() {
        var data = {
            date_range: $(this).val()
        };

        if ($(this).val() != 'Custom') {
            $.getJSON('/api/octommerce/report/data', data, function (dataTableJson) {
                lava.loadData('MyStocks', dataTableJson, function (chart) {
                    console.log(chart);
                });
            });
            $('.custom-date-range').prop('disabled', true);
        }
        else {
            $('.custom-date-range').prop('disabled', false);
        }
    });

    $('.custom-date-range').on('change', function() {
        var data = {
            date_range: $('select[name="date_range"]').val(),
            start_date: $('input[name="start_date"]').val(),
            end_date: $('input[name="end_date"]').val(),
        };

        $.getJSON('/api/octommerce/report/data', data, function (dataTableJson) {
            lava.loadData('MyStocks', dataTableJson, function (chart) {
                console.log(chart);
            });
        });
    });
});
