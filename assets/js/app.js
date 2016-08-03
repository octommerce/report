$(document).ready(function () {
    $('select[name="date_range"]').on('change', function() {
        var data = {
            date_range: $(this).val()
        };

        if ($(this).val() != 'Custom') {
            $.getJSON('/api/octommerce/report/data', data, function (data) {
                lava.loadData('MyStocks', data['dataTable'], function (chart) {
                });
                $('#summary-revenue').html(data['revenue']) ;
                $('#summary-transactions').html(data['transactions']) ;
                $('#summary-avg-order').html(data['avgOrder']) ;
                $('#summary-products-sold').html(data['productsSold']) ;
            });
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
