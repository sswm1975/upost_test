<?php

namespace Encore\Admin\Scheduling;

use Encore\Admin\Admin;
use Encore\Admin\Extension;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Support\Str;

class Scheduling extends Extension
{
    /**
     * @var string out put file for command.
     */
    protected $sendOutputTo;

    /**
     * Get all events in console kernel.
     *
     * @return array
     */
    protected function getKernelEvents()
    {
        app()->make('Illuminate\Contracts\Console\Kernel');

        return app()->make('Illuminate\Console\Scheduling\Schedule')->events();
    }

    /**
     * Get all formatted tasks.
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getTasks()
    {
        $tasks = [];

        foreach ($this->getKernelEvents() as $event) {
            $tasks[] = [
                'task'          => $this->formatTask($event),
                'expression'    => $event->expression,
                'nextRunDate'   => $event->nextRunDate()->format('Y-m-d H:i:s'),
                'description'   => $event->description,
                'readable'      => CronSchedule::fromCronString($event->expression)->asNaturalLanguage(),
                'output'        => $event->output != '/dev/null' ? $event->output : '',
            ];
        }

        return $tasks;
    }

    /**
     * Format a giving task.
     *
     * @param $event
     *
     * @return array
     */
    protected function formatTask($event)
    {
        if ($event instanceof CallbackEvent) {
            $name = $event->output != '/dev/null' ? current(explode('.', basename($event->output))) : 'Closure';
            return [
                'type' => 'closure',
                'name' => 'LOG: ' . $name,
            ];
        }

        if (Str::contains($event->command, '\'artisan\'')) {
            $exploded = explode(' ', $event->command);

            return [
                'type' => 'artisan',
                'name' => 'artisan '.implode(' ', array_slice($exploded, 2)),
            ];
        }

        if (PHP_OS_FAMILY === 'Windows' && Str::contains($event->command, '"artisan"')) {
            $exploded = explode(' ', $event->command);

            return [
                'type' => 'artisan',
                'name' => 'artisan '.implode(' ', array_slice($exploded, 2)),
            ];
        }

        return [
            'type' => 'command',
            'name' => $event->command,
        ];
    }

    /**
     * Run specific task.
     *
     * @param int $id
     *
     * @return string
     */
    public function runTask($id)
    {
        set_time_limit(0);

        /** @var \Illuminate\Console\Scheduling\Event $event */
        $event = $this->getKernelEvents()[$id - 1];

        if (PHP_OS_FAMILY === 'Windows') {
            $event->command = Str::of($event->command)->replace('php-cgi.exe', config('admin.php'));
            $event->command = Str::of($event->command)->replace('""', config('admin.php'));
        }

        $event->sendOutputTo($this->getOutputTo($event->output));

        $event->run(app());

        return $this->readOutput();
    }

    /**
     * Load log file.
     *
     * @param int $id
     *
     * @return string
     */
    public function loadLog($id)
    {
        /** @var \Illuminate\Console\Scheduling\Event $event */
        $event = $this->getKernelEvents()[$id - 1];

        if (!file_exists($event->output)) {
            return "File {$event->output} not exists!";
        }

        return file_get_contents($event->output);
    }

    /**
     * @return string
     */
    protected function getOutputTo($event_output = '')
    {
        if (!$this->sendOutputTo) {
            $this->sendOutputTo = !empty($event_output) ? $event_output : storage_path('app/task-schedule.output');
        }

        return $this->sendOutputTo;
    }

    /**
     * Read output info from output file.
     *
     * @return string
     */
    protected function readOutput()
    {
        return file_get_contents($this->getOutputTo());
    }

    /**
     * Bootstrap this package.
     *
     * @return void
     */
    public static function boot()
    {
        static::registerRoutes();

        Admin::extend('scheduling', __CLASS__);
    }

    /**
     * Register routes for laravel-admin.
     *
     * @return void
     */
    protected static function registerRoutes()
    {
        parent::routes(function ($router) {
            /* @var \Illuminate\Routing\Router $router */
            $router->get('helpers/scheduling', 'Encore\Admin\Scheduling\SchedulingController@index')->name('scheduling-index');
            $router->post('helpers/scheduling/run', 'Encore\Admin\Scheduling\SchedulingController@runEvent')->name('scheduling-run');
            $router->post('helpers/scheduling/load-log', 'Encore\Admin\Scheduling\SchedulingController@loadLog')->name('scheduling-load_log');
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function import()
    {
        parent::createMenu('Scheduling', 'scheduling', 'fa-clock-o');

        parent::createPermission('Scheduling', 'ext.scheduling', 'scheduling*');
    }
}
