<?php namespace Octommerce\Report\Http;

use Db;
use Lava;
use Input;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Octommerce\Octommerce\Models\Order;

class ReportController extends Controller
{
    /**
     * Get orders data
     *
     * @return $data
     */
    public function getData()
    {
        $isSales = false;
        $date = $this->getStartAndEndDate(Input::get('date_range'));
        $startDate = Carbon::parse($date['start_date']);
        $endDate = Carbon::parse($date['end_date']);

        $stocksTable = Lava::DataTable();

        $stocksTable->addDateColumn('Date')
                    ->addNumberColumn('Orders')
                    ->addNumberColumn('Sales');

        // lists() does not accept raw queries,
        // so you have to specify the SELECT clause
        $days = Order::select(array(
                Db::raw('DATE(`created_at`) as `date`'),
                Db::raw('SUM(subtotal) as `amount`')
            ));

        $dataOrders = $days->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->lists('amount', 'date');

        $dataSales = $days->sales()->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->lists('amount', 'date');

        $salesOrders = Order::with('products')
            ->sales()
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->get();

        $points = [];

        while ($endDate->diffInDays($startDate)) {

            $stocksTable->addRow([
                $endDate->format('Y-m-d'),
                isset($dataOrders[$endDate->format('Y-m-d')]) ? $dataOrders[$endDate->format('Y-m-d')] : 0,
                isset($dataSales[$endDate->format('Y-m-d')]) ? $dataSales[$endDate->format('Y-m-d')] : 0,
            ]);

            $endDate->subDays(1);
        }

        $data = [
            'dataTable'    => json_decode($stocksTable->toJson(), true),
            'revenue'      => number_format($salesOrders->sum('subtotal')),
            'transactions' => $salesOrders->count(),
            'avgOrder'     => $this->getAverageOrder($salesOrders),
            'productsSold' => $this->getProductsSoldQty($salesOrders)
        ];

        return $data;
    }

    /**
     * Get average order
     *
     * @param $orders
     * @return $avgOrder
     */
    public function getAverageOrder($orders)
    {
        $avgOrder = 0;

        if ($orders->count()) {
            $avgOrder = $orders->sum('subtotal') / $orders->count();
        }

        return number_format($avgOrder);
    }

    /**
     * Get products sold quantity
     *
     * @param $orders
     * @return $productsSold
     */
    public function getProductsSoldQty($orders)
    {
        $productsSold = $orders->sum(function($order) {
            return $order->products->sum('pivot.qty');
        });

        return $productsSold;
    }

    /**
     * Get start and end date
     *
     * @param $dataRange
     * @return $date
     **/
    private function getStartAndEndDate($dateRange)
    {
        $startDate = '';
        $endDate = '';

        switch ($dateRange) {
            case 'Last 7 days':
                $startDate = Carbon::now()->subDays(7);
                $endDate = Carbon::now();
                break;
            case 'Last 30 days':
                $startDate = Carbon::now()->subDays(30);
                $endDate = Carbon::now();
                break;
            case 'This month':
                $startDate = new Carbon('first day of this month');
                $endDate = new Carbon('last day of this month');
                break;
            case 'Last month':
                $startDate = new Carbon('first day of last month');
                $endDate = new Carbon('last day of last month');
                break;
            case 'This year':
                $startDate = new Carbon('first day of January ' . date('Y'));
                $endDate = new Carbon('last day of December ' . date('Y'));
                break;
            case 'Custom':
                $startDate = Carbon::parse(Input::get('start_date'));
                $endDate = Carbon::parse(Input::get('end_date'))->addDays(1);
                break;
        }

        $date = [
            'start_date' => $startDate,
            'end_date'   => $endDate
        ]; 

        return $date;
    }
    
}
