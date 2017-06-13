<?php namespace Octommerce\Report\Models;

use Carbon\Carbon;
use Octommerce\Octommerce\Models\Brand;
use Octommerce\Octommerce\Models\OrderDetail;
use Octommerce\Octommerce\Models\Order;
use Octommerce\Report\Classes\ReportManager;

class BrandExport extends \Backend\Models\ExportModel
{

    protected $fillable = ['start_date', 'end_date', 'brands'];

    public function exportData($columns, $sessionKey = null)
    {
        $query = Order::with('products.brand')
            ->sales();

        if ($this->brands) {
            $query->with(['products' => function($query) {
                $query->whereIn('brand_id', $this->brands);
            }]);
        }

        if ($this->start_date) {
            $query->whereDate('created_at', '>=', Carbon::parse($this->start_date)->format('Y-m-d'));
        }

        if ($this->end_date) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->start_date)->format('Y-m-d'));
        }

        return $this->exportByBrand($query->get());
    }

    public function getBrandsOptions()
    {
        return Brand::lists('name', 'id');
    }

    public function exportByBrand($orders)
    {
        $products = collect();

        $totalRevenue = 0;

        foreach ($orders as $order) {
            foreach ($order->products as $product) {

                if (!isset($products[$product->id])) {
                    $products[$product->id] = collect([
                        'id'         => $product->id,
                        'name'       => $product->name,
                        'brand'      => $product->brand->name,
                        'sku'        => $product->sku,
                        'qty'        => 0,
                        'sales'      => 0,
                        'revenue'    => 0,
                        'percentage' => 0,
                    ]);
                }

                $products[$product->id]['qty'] += $product->pivot->qty;
                $products[$product->id]['sales'] += 1;
                $products[$product->id]['revenue'] += $product->pivot->qty * $product->pivot->price;

                $totalRevenue += $product->pivot->qty * $product->pivot->price;
            }
        }

        return $products->sortByDesc('revenue')->map(function($product) use ($totalRevenue) {
            $product['percentage'] = $product['revenue'] / $totalRevenue * 100;

            return $product;
        });
    }
}
