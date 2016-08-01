$(document).ready(function () {
    $('select[name="date_range"]').on('change', function() {
        var data = {
            date_range: $(this).val()
        };
        $.getJSON('/api/octommerce/report/data', data, function (dataTableJson) {
            lava.loadData('MyStocks', dataTableJson, function (chart) {
                console.log(chart);
            });
        });
    });
});
