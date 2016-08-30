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
     * @param $data
     * @return $data
     */
    public function getData($data)
    {
        $date = $this->getStartAndEndDate($data['date_range'], $data['start_date'], $data['end_date']);
        $startDate = Carbon::parse($date['start_date']);
        $endDate = Carbon::parse($date['end_date']);

        $salesOrders = Order::with('products', 'products.brand', 'products.categories', 'invoices.payment_method', 'city')
            ->sales()
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->get();

        $stocksTable = $this->getDataTable($data);
        $topCategories = $this->getTopCategories($salesOrders);

        $data = [
            'dataTable'           => json_decode($stocksTable->toJson(), true),
            'dataTableCategories' => json_decode($this->getDataTableForCategories($topCategories), true),
            'revenue'             => $salesOrders->sum('subtotal'),
            'transactions'        => $salesOrders->count(),
            'avgOrder'            => $this->getAverageOrder($salesOrders),
            'productsSold'        => $this->getProductsSoldQty($salesOrders),
            'topProducts'         => $this->getTopProducts($salesOrders),
            'topCategories'       => $topCategories,
            'topBrands'           => $this->getTopBrands($salesOrders),
            'topPaymentMethods'   => $this->getTopPaymentMethods($salesOrders),
            'topLocations'        => $this->getTopLocations($salesOrders)
        ];

        return $data;
    }

    /**
     * Get data table
     * 
     * @param $data
     * @return $stocksTable
     */
    public function getDataTable($data)
    {
        $isSales = false;

        $type = isset($data['type']) ? $data['type'] : 'revenue';
        $interval = isset($data['interval']) ? $data['interval'] : 'date';

        $date = $this->getStartAndEndDate($data['date_range'], $data['start_date'], $data['end_date']);
        $startDate = Carbon::parse($date['start_date']);
        $endDate = Carbon::parse($date['end_date']);

        $stocksTable = Lava::DataTable();

        if ($interval == 'date') {
            $stocksTable->addDateColumn('Date')
                        ->addNumberColumn('Orders')
                        ->addNumberColumn('Sales');
        }
        else {
            $stocksTable->addStringColumn('Date')
                        ->addNumberColumn('Orders')
                        ->addNumberColumn('Sales');
        }

        $amountRaw = $type == 'revenue' ? 'SUM(subtotal)' : 'COUNT(*)';
        $dateRaw = $this->getSelectRawFormat($interval);
        // lists() does not accept raw queries,
        // so you have to specify the SELECT clause
        $days = Order::select(array(
                Db::raw('TIMESTAMP(created_at) as `timestamp`'),
                Db::raw($dateRaw . ' as `date`'),
                Db::raw($amountRaw . ' as `amount`')
            ));

        $dataOrders = $days->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->groupBy('date')
            ->orderBy(Db::raw($this->getOrderByRawFormat($interval)), 'ASC')
            ->get();

        $dataSales = $days->sales()->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->groupBy('date')
            ->orderBy(Db::raw($this->getOrderByRawFormat($interval)), 'ASC')
            ->get();

        $points = [];

        if ($dataSales->count() >= $dataOrders->count()) {
            foreach ($dataSales as $dataSale) {

                $dateFormated = $this->getFormatDateBasedInterval($dataSale->timestamp, $interval);

                $stocksTable->addRow([
                    $dateFormated,
                    $this->getAmountValue($dataOrders, $dataSale->date),
                    $dataSale->amount
                ]);
            }
        }
        else {
            foreach ($dataOrders as $dataOrder) {

                $dateFormated = $this->getFormatDateBasedInterval($dataOrder->timestamp, $interval);

                $stocksTable->addRow([
                    $dateFormated,
                    $dataOrder->amount,
                    $this->getAmountValue($dataSales, $dataOrder->date)
                ]);
            }
        }

        return $stocksTable;
    }

    /**
     * Get amount value in collection
     *
     * @param  $collection
     * @param  $date
     * 
     * @return $amount
     **/
    public function getAmountValue($collection, $date) 
    {
        $data = $collection->first(function($key, $value) use ($date) {
            return $value['date'] == $date; 
        }); 

        if (! $data) {
            return 0; 
        }

        return $data->amount;
    }

    public function getSelectRawFormat($interval) 
    {
        $format = $this->getGroupByRawFormat($interval);
        $format = str_replace('(date)', '(created_at)', $format);

        return $format;
    }

    public function getGroupByRawFormat($interval)
    {
        $rawFormat = '';

        switch($interval) {
            case 'date':
                $rawFormat = 'DATE(date)';
                break; 
            case 'month':
                $rawFormat = 'MONTH(date)';
                break; 
            case 'week':
                $rawFormat = 'WEEKOFYEAR(date)';
                break; 
            case 'day':
                $rawFormat = 'DAYNAME(date)';
                break; 
            case 'time':
                $rawFormat = 'HOUR(date)';
                break; 
        }

        return $rawFormat;
    }

    public function getOrderByRawFormat($interval)
    {
        $rawFormat = '';

        switch($interval) {
            case 'date':
                $rawFormat = 'DATE(created_at)';
                break; 
            case 'month':
                $rawFormat = 'MONTH(created_at)';
                break; 
            case 'week':
                $rawFormat = 'WEEKOFYEAR(created_at)';
                break; 
            case 'day':
                $rawFormat = 'DAYOFWEEK(created_at)';
                break; 
            case 'time':
                $rawFormat = 'HOUR(created_at)';
                break; 
        }

        return $rawFormat;
    }

    public function getFormatDateBasedInterval($date, $interval)
    {
        $dateFormated = '';

        switch($interval) {
            case 'date':
                $dateFormated = Carbon::parse($date)->format('M d, Y');
                break; 
            case 'month':
                $dateFormated = Carbon::parse($date)->format('M');
                break; 
            case 'week':
                $dateFormated = Carbon::parse($date)->format('W');
                break; 
            case 'day':
                $dateFormated = Carbon::parse($date)->format('l');
                break; 
            case 'time':
                $dateFormated = Carbon::parse($date)->format('H');
                break; 
        }

        return $dateFormated;
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
     * Get top categories
     * @param  $orders
     * @return Collection
     */
    public function getTopCategories($orders)
    {
        $categories = collect();

        $totalRevenue = 0;

        foreach ($orders as $order) {
            foreach ($order->products as $product) {
                foreach($product->categories as $category) {
                    if (!isset($categories[$category->id])) {
                        $categories[$category->id] = collect([
                            'id' => $category->id,
                            'name' => $category->name,
                            'sales' => 0,
                            'revenue' => 0,
                            'percentage' => 0,
                        ]);
                    }

                    $categories[$category->id]['revenue'] += $product->pivot->qty * $product->pivot->price;
                    $categories[$category->id]['sales'] += 1;
                    $totalRevenue += $product->pivot->qty * $product->pivot->price;
                }
            }
        }

        $categories = $categories->sortByDesc('revenue')->take(10)->map(function($category) use ($totalRevenue) {
            $category['percentage'] = $category['revenue'] / $totalRevenue * 100;

            return $category;
        });

        return $categories;
    }

    /**
     * Get top brands
     * @param  $orders
     * @return Collection
     */
    public function getTopBrands($orders)
    {
        $brands = collect();

        $totalRevenue = 0;

        foreach ($orders as $order) {
            foreach ($order->products as $product) {

                if (!isset($brands[$product->brand->id])) {
                    $brands[$product->brand->id] = collect([
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                        'sales' => 0,
                        'revenue' => 0,
                        'percentage' => 0,
                    ]);
                }

                $brands[$product->brand->id]['sales'] += 1;
                $brands[$product->brand->id]['revenue'] += $product->pivot->qty * $product->pivot->price;

                $totalRevenue += $product->pivot->qty * $product->pivot->price;
            }
        }

        $brands = $brands->sortByDesc('revenue')->take(10)->map(function($brand) use ($totalRevenue) {
            $brand['percentage'] = $brand['revenue'] / $totalRevenue * 100;

            return $brand;
        });

        return $brands;
    }

    /**
     * Get top payment methods
     * @param  $orders
     * @return Collection
     */
    public function getTopPaymentMethods($orders)
    {
        $paymentMethods = collect();

        $totalRevenue = 0;

        foreach ($orders as $order) {
            foreach ($order->invoices as $invoice) {

                if (!isset($paymentMethods[$invoice->payment_method_id])) {
                    $paymentMethods[$invoice->payment_method_id] = collect([
                        'id' => $invoice->payment_method_id,
                        'name' => $invoice->payment_method->name,
                        'sales' => 0,
                        'revenue' => 0,
                        'percentage' => 0,
                    ]);
                }

                $paymentMethods[$invoice->payment_method_id]['sales'] += 1;
                $paymentMethods[$invoice->payment_method_id]['revenue'] += $invoice->total;

                $totalRevenue += $invoice->total;
            }
        }

        $paymentMethods = $paymentMethods->sortByDesc('revenue')->take(10)->map(function($paymentMethod) use ($totalRevenue) {
            $paymentMethod['percentage'] = $paymentMethod['revenue'] / $totalRevenue * 100;

            return $paymentMethod;
        });

        return $paymentMethods;
    }

    /**
     * Get top locations
     * @param  $orders
     * @return Collection
     */
    public function getTopLocations($orders)
    {
        $locations = collect();

        $totalRevenue = 0;

        foreach ($orders as $order) {

            if (! $order->city) continue;

            if (!isset($locations[$order->city_id])) {
                $locations[$order->city_id] = collect([
                    'id' => $order->city_id,
                    'name' => $order->city->name,
                    'sales' => 0,
                    'revenue' => 0,
                    'percentage' => 0,
                ]);
            }

            $locations[$order->city_id]['sales'] += 1;
            $locations[$order->city_id]['revenue'] += $order->total;

            $totalRevenue += $order->total;
        }

        $locations = $locations->sortByDesc('revenue')->take(10)->map(function($location) use ($totalRevenue) {
            $location['percentage'] = $location['revenue'] / $totalRevenue * 100;

            return $location;
        });

        return $locations;
    }

    /**
     * Get dataTable for categories
     *
     * @return json
     */
    public function getDataTableForCategories($categories)
    {
        $table = Lava::DataTable(); 

        $table->addStringColumn('Categories')
            ->addNumberColumn('Revenue');

        foreach ($categories as $category) {
            $table->addRow([
                $category['name'],
                $category['revenue']
            ]);
        }

        return $table->toJson();
    }

    /**
     * Get start and end date
     *
     * @param $dataRange
     * @return $date
     **/
    private function getStartAndEndDate($dateRange, $startDate = null, $endDate = null)
    {
        $startDate = $startDate;
        $endDate = $endDate;

        switch ($dateRange) {
            case 'Last 7 days':
                $startDate = Carbon::today()->subDays(6);
                $endDate = Carbon::today();
                break;
            case 'Last 30 days':
                $startDate = Carbon::today()->subDays(29);
                $endDate = Carbon::today();
                break;
            case 'This month':
                $startDate = Carbon::today()->startOfMonth();
                $endDate = Carbon::today();
                break;
            case 'Last month':
                $startDate = new Carbon('first day of last month');
                $endDate = new Carbon('last day of last month');
                break;
            case 'This year':
                $startDate = Carbon::today()->startOfYear();
                $endDate = Carbon::today();
                break;
            case 'Custom':
                $startDate = Carbon::parse($startDate)->toDateString();
                $endDate = Carbon::parse($endDate)->toDateString();
        }

        $date = [
            'start_date' => $startDate,
            'end_date'   => $endDate
        ];

        return $date;
    }
}
