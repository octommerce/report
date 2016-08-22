<?php namespace Octommerce\Report;

use App;
use Event;
use Backend;
use System\Classes\PluginBase;
use Illuminate\Foundation\AliasLoader;

/**
 * Report Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['Octommerce.Octommerce'];

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        // Service provider
        App::register('\Khill\Lavacharts\Laravel\LavachartsServiceProvider');

        // Register alias
        $alias = AliasLoader::getInstance();
        $alias->alias('Lava', '\Khill\Lavacharts\Laravel\LavachartsFacade');

        Event::listen('backend.menu.extendItems', function($manager) {
            $manager->addSideMenuItems('Octommerce.Octommerce', 'commerce', [
                'reports' => [
                    'label'       => 'Report',
                    'url'         => Backend::url('octommerce/report/reports'),
                    'icon'        => 'icon-bar-chart',
                    'permissions' => ['octommerce.report.access_reports'],
                ]
            ]);
        });
    }
}
