<?php namespace Octommerce\Report\Classes;

use Db;
use Lava;
use Carbon\Carbon;
use RainLab\User\Models\User;
use Octommerce\Octommerce\Models\Order;
use Octommerce\Octommerce\Models\Cart;
use Octommerce\Octommerce\Models\City;
use Octommerce\Octommerce\Models\Product;
use Octommerce\Octommerce\Models\Brand;
use Responsiv\Currency\Facades\Currency;


class ReportManager
{
	use \October\Rain\Support\Traits\Singleton;

    /**
     * Get orders data
     *
     * @return $data
     */
    public function getData($dateRange, $startDate = null, $endDate = null)
    {
        $isSales = false;

        $date = $this->getStartAndEndDate($dateRange, $startDate, $endDate);
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
            'revenue'      => $salesOrders->sum('subtotal'),
            'transactions' => $salesOrders->count(),
            'avgOrder'     => $this->getAverageOrder($salesOrders),
            'productsSold' => $this->getProductsSoldQty($salesOrders),
            'topProducts'  => $this->getTopProducts($salesOrders),
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

        return $avgOrder;
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
     * Get top products
     * @param  $orders
     * @return Collection
     */
    public function getTopProducts($orders)
    {
        $products = collect();

        $totalRevenue = 0;

        foreach ($orders as $order) {
            // dd($order->products()->get());
            foreach ($order->products as $product) {

                if (!isset($products[$product->id])) {
                    $products[$product->id] = collect([
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'qty' => 0,
                        'sales' => 0,
                        'revenue' => 0,
                        'avg_price' => 0,
                        'percentage' => 0,
                    ]);
                }

                $products[$product->id]['qty'] += $product->pivot->qty;
                $products[$product->id]['sales'] += 1;
                $products[$product->id]['revenue'] += $product->pivot->qty * $product->pivot->price;

                $totalRevenue += $product->pivot->qty * $product->pivot->price;
            }
        }

        $products = $products->sortByDesc('revenue')->take(10)->map(function($product) use ($totalRevenue) {
            $product['avg_price'] = $product['revenue'] / $product['sales'];
            $product['percentage'] = $product['revenue'] / $totalRevenue * 100;

            return $product;
        });

        return $products;
    }

    /**
     * Get start and end date
     *
     * @param $dataRange
     * @return $date
     **/
    private function getStartAndEndDate($dateRange, $startDate, $endDate)
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
                $startDate = $startDate;
                $endDate = $endDate->addDays(1);
                break;
        }

        $date = [
            'start_date' => $startDate,
            'end_date'   => $endDate
        ];

        return $date;
    }
}
