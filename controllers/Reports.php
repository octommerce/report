<?php namespace Octommerce\Report\Controllers;

use Db;
use Lava;
use BackendMenu;
use Carbon\Carbon;
use Backend\Classes\Controller;
use Octommerce\Octommerce\Models\Order;
use Octommerce\Octommerce\Models\Product;
use Octommerce\Octommerce\ReportWidgets\Summary as SummaryWidget;

/**
 * Reports Back-end Controller
 */
class Reports extends Controller
{

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Octommerce.Octommerce', 'commerce', 'reports');

        $summaryWidget = new SummaryWidget($this);
        $summaryWidget->alias = 'summary';
        $summaryWidget->bindToController();
    }

    public function index()
    {
        $this->AddJs('/plugins/octommerce/report/assets/js/app.js');

        $this->bodyClass = 'compact-container';

        // $this->vars['dataAllOrders'] = $this->getLastOrdersData(30, false);
        // $this->vars['dataPaidOrders'] = $this->getLastOrdersData(30);

        $this->chart();
    }

    protected function chart()
    {
        $duration = 30;
        $isSales = false;

        $stocksTable = Lava::DataTable();

        $stocksTable->addDateColumn('Date')
                    ->addNumberColumn('Orders')
                    ->addNumberColumn('Sales');

        $date = Carbon::now()->subDays($duration - 1);

        // lists() does not accept raw queries,
        // so you have to specify the SELECT clause
        $days = Order::select(array(
                Db::raw('DATE(`created_at`) as `date`'),
                Db::raw('SUM(subtotal) as `amount`')
            ));

        $dataOrders = $days->where('created_at', '>', $date)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->lists('amount', 'date');

        $dataSales = $days->sales()
            ->where('created_at', '>', $date)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->lists('amount', 'date');

        $points = [];

        for ($i = $duration - 1; $i >= 0; $i--) {

            $date = Carbon::now()->subDays($i);

            $stocksTable->addRow([
                $date->format('Y-m-d'),
                isset($dataOrders[$date->format('Y-m-d')]) ? $dataOrders[$date->format('Y-m-d')] : 0,
                isset($dataSales[$date->format('Y-m-d')]) ? $dataSales[$date->format('Y-m-d')] : 0,
            ]);

        }

        $chart = Lava::AreaChart('MyStocks', $stocksTable, [
            'title' => 'Sales',
            'colors' => ['#ddd', 'green'],
            ]);
    }

    protected function getLastOrdersData($duration = 30, $isSales = true)
    {

        $date = Carbon::now()->subDays($duration - 1);

        // lists() does not accept raw queries,
        // so you have to specify the SELECT clause
        $days = Order::select(array(
                Db::raw('DATE(`created_at`) as `date`'),
                Db::raw('SUM(subtotal) as `amount`')
            ));

        if ($isSales) {
            $days = $days->sales();
        }

        $days = $days->where('created_at', '>', $date)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->lists('amount', 'date');

        $points = [];

        for ($i = $duration - 1; $i >= 0; $i--) {
           $date = Carbon::now()->subDays($i);

           $points[] = [
               $date->timestamp * 1000,
               isset($days[$date->format('Y-m-d')]) ? $days[$date->format('Y-m-d')] : 0,
           ];
        }

        // Parse format
        return str_replace('"', '', substr(substr(json_encode($points), 1), 0, -1));
    }

}