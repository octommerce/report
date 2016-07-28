<?php namespace Octommerce\Report;

use App;
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
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Report',
            'description' => 'No description provided yet...',
            'author'      => 'Octommerce',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

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
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Octommerce\Report\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'octommerce.report.some_permission' => [
                'tab' => 'Report',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'report' => [
                'label'       => 'Report',
                'url'         => Backend::url('octommerce/report/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['octommerce.report.*'],
                'order'       => 500,
            ],
        ];
    }

}
