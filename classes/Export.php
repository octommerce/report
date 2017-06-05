<?php namespace Octommerce\Report\Classes;

use Cache;
use Excel;
use Octommerce\Octommerce\Models\Brand;

class Export
{
    /**
     * Define sheet data
     *
     * 'key' => 'Sheet name' 
     **/
    private $sheets = [
        'productReportData'       => 'Products',
        'categoryReportData'      => 'Categories',
        'brandReportData'         => 'Brands',
        'paymentMethodReportData' => 'Payment methods',
        'locationReportData'      => 'Locations',
    ];

    public static function report()
    {
        return new static;
    }
    
    public  function excel($filename = 'export_report')
    {
        return Excel::create($filename, function($excel) {
            array_walk($this->sheets, function($name, $key) use ($excel) {
                $excel->sheet($name, $this->sheetCallback($key));
            });
        });
    }

    public function store()
    {
        return $this->store('xls');
    }

    public function download()
    {
        return $this->download('xls');
    }
    
    protected function sheetCallback($key)
    {
        return function($sheet) use ($key) {
            $sheet->fromModel( 
                collect($this->getCacheData($key))
            );
        };
    }

    private function getCacheData($key)
    {
        return Cache::get($key);
    }
}
