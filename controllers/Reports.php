<?php namespace Octommerce\Report\Controllers;

use Db;
use Lava;
use Redirect;
use BackendMenu;
use Carbon\Carbon;
use Backend\Classes\Controller;
use Octommerce\Octommerce\Models\Order;
use Octommerce\Octommerce\Models\Product;
use Octommerce\Report\Classes\ReportManager;
use Responsiv\Currency\Facades\Currency;

/**
 * Reports Back-end Controller
 */
class Reports extends Controller
{
    public $reportManager;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Octommerce.Octommerce', 'commerce', 'reports');

        $this->reportManager = ReportManager::instance();
    }

    public function index()
    {
        $this->AddJs('/plugins/octommerce/report/assets/js/app.js');
        $this->AddCSS('/plugins/octommerce/report/assets/css/app.css');

        $this->pageTitle = 'Report';

        $this->initChart();
    }

    public function index_onLoad()
    {
        $data = $this->reportManager->getData(post());

        $this->vars['topProducts'] = $data['topProducts'];
        $this->vars['topCategories'] = $data['topCategories'];
        $this->vars['topBrands'] = $data['topBrands'];
        $this->vars['topPaymentMethods'] = $data['topPaymentMethods'];
        $this->vars['topLocations'] = $data['topLocations'];

        return [
            'dataTable'                    => $data['dataTable'],
            'dataTableCategories'          => $data['dataTableCategories'],
            '#report-summary-revenue'      => Currency::format($data['revenue']),
            '#report-summary-transactions' => $data['transactions'],
            '#report-summary-avgOrder'     => Currency::format($data['avgOrder']),
            '#report-summary-productsSold' => $data['productsSold'],
        ];
    }

    public function index_onExportResult()
    {
        return Redirect::route('report.download');
    }

    public function index_onLoadByFilter()
    {
        $stocksTable = $this->reportManager->getDataTable(post());

        return [
            'dataTable' => json_decode($stocksTable->toJson(), true)
        ];
    }

    protected function initChart()
    {
        $table = Lava::DataTable();

        $table->addDateColumn('Date')
                    ->addNumberColumn('Orders')
                    ->addNumberColumn('Sales');

        $table->addRow([Carbon::now()->format('Y-m-d'), 0, 0]);

        $chart = Lava::AreaChart('orders', $table, [
            'title' => 'Sales',
            'colors' => ['#ddd', 'green'],
            'events' => [
                'ready' => 'initReport'
            ]
        ]);

        $this->initBarChart();
    }

    protected function initBarChart()
    {
        $table = Lava::DataTable(); 

        $table->addStringColumn('Categories')
            ->addNumberColumn('Revenue');

        $table->addRow(['', 0]);

        $chart = Lava::BarChart('categories', $table, [
            'events' => [
                'ready' => 'initReport' 
            ],
            'legend' => [
                'position' => 'bottom' 
            ] 
        ]);
    }
}
