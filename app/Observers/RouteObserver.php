<?php

namespace App\Observers;

use App\Models\Action;
use App\Models\Route;

class RouteObserver
{
    /**
     * Handle the route "created" event.
     *
     * @param Route $route
     * @return void
     */
    public function created(Route $route)
    {
        $this->addAction($route, Action::ROUTE_CREATED);
    }

    /**
     * Handle the route "updated" event.
     *
     * @param Route $route
     * @return void
     */
    public function updated(Route $route)
    {
        # изменился статус маршрута
        if ($route->wasChanged(['status'])) {
            $this->addAction($route, Action::ROUTE_STATUS_CHANGED);
        }

        # изменены данные маршрута
        if ($route->wasChanged(['from_country_id', 'from_city_id', 'to_country_id', 'to_city_id', 'deadline']))  {
            $this->addAction($route, Action::ROUTE_UPDATES);
        }
    }

    /**
     * Handle the route "deleted" event.
     *
     * @param Route $route
     * @return void
     */
    public function deleted(Route $route)
    {
        $this->addAction($route, Action::ROUTE_DELETED);
    }

    /**
     * Handle the route "restored" event.
     *
     * @param Route $route
     * @return void
     */
    public function restored(Route $route)
    {
        $this->addAction($route, Action::ROUTE_RESTORED);
    }

    /**
     * Handle the route "force deleted" event.
     *
     * @param Route $route
     * @return void
     */
    public function forceDeleted(Route $route)
    {
        $this->addAction($route, Action::ROUTE_DELETED);
    }

    /**
     * Add action for user's model.
     *
     * @param Route $route
     * @param string $name
     */
    private function addAction(Route $route, string $name)
    {
        $auth_user_id = request()->user()->id ?? 0;

        Action::create([
            'user_id'  => $route->user_id,
            'is_owner' => $auth_user_id == $route->user_id,
            'name'     => $name,
            'changed'  => $route->getChanges(),
            'data'     => $route,
        ]);
    }
}
