<?php

namespace App\Platform\Controllers;

use App\Models\Chat;
use Encore\Admin\Grid;

class ChatController extends AdminController
{
    protected string $title = 'Чаты';
    protected string $icon = 'fa-commenting';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Chat);

        $grid->disablePagination(false);
        $grid->disableFilter(false);
        $grid->disableCreateButton();
        $grid->paginate(20);
        $grid->disableActions();

        # FILTERS
        $grid->filter(function($filter) {
            $filter->disableIdFilter();

            $filter->scope('active', 'Активные')->active()->asDefault();
            $filter->scope('closed', 'Закрытые')->closed();
            $filter->scope('dispute', 'Есть спор')->existsDispute();
        });

        # MODEL
        $grid->model()
            ->selectRaw("
                chats.id,
                chats.status,
                chats.created_at,
                chats.updated_at,

                IFNULL((SELECT COUNT(1) FROM messages WHERE chat_id = chats.id), 0) AS messages_cnt,

                chats.customer_id,
                TRIM(CONCAT(uc.`surname`, ' ', uc.`name`)) AS customer_name,
                uc.phone AS customer_phone,
                uc.email AS customer_email,
                uc.photo AS customer_photo,
                chats.customer_unread_count,

                chats.order_id,
                o.`name` AS order_name,
                o.`description` AS order_description,
                o.product_link AS order_url,
                o.price AS order_price,
                o.currency AS order_currency,
                o.products_count AS order_products_count,
                o.user_price AS order_profit_price,
                o.user_currency AS order_profit_currency,
                o.deadline AS order_deadline,
                o.images AS order_images,

                chats.performer_id,
                TRIM(CONCAT(up.`surname`, ' ', up.`name`)) AS performer_name,
                up.phone AS performer_phone,
                up.email AS performer_email,
                up.photo AS performer_photo,
                chats.performer_unread_count,

                chats.route_id,
                cntfr.name_ru AS route_from_country,
                cfr.name_ru AS route_from_city,
                cnttr.name_ru AS route_to_country,
                ctr.name_ru AS route_to_city,
                r.deadline AS route_deadline,

                EXISTS (SELECT 1 FROM disputes WHERE chat_id = chats.id) AS exists_dispute
            ")
            ->join('users as uc','uc.id', 'chats.customer_id')
            ->join('users as up','up.id', 'chats.performer_id')
            ->join('orders AS o','o.id', 'chats.order_id')
            ->join('routes AS r','r.id', 'chats.route_id')
            ->join('countries AS cntfr','cntfr.id', 'r.from_country_id')
            ->join('countries AS cnttr','cnttr.id', 'r.to_country_id')
            ->leftJoin('cities AS cfr','cfr.id', 'r.from_city_id')
            ->leftJoin('cities AS ctr','ctr.id', 'r.to_city_id');

        # COLUMNS
        $grid->column('id', )->sortable();
        $grid->column('status')->sortable();
        $grid->column('created_at')->sortable();
        $grid->column('updated_at')->sortable();
        $grid->column('messages_cnt')->sortable();

        $grid->column('customer_id', 'CId')->sortable();
        $grid->column('customer_name', 'Customer');
        $grid->column('customer_phone', 'Customer phone');
        $grid->column('customer_email', 'Customer email');

        $grid->column('order_id', 'OId')->sortable();
        $grid->column('order_name', 'Order');
        $grid->column('order_price', 'Price');
        $grid->column('order_currency', '#');
        $grid->column('order_profit_price', 'Profit');
        $grid->column('order_profit_currency', '#');

        $grid->column('performer_id', 'PId')->sortable();
        $grid->column('performer_name', 'Performer');
        $grid->column('performer_phone', 'Performer phone');
        $grid->column('performer_email', 'Performer email');

        $grid->column('route_id', 'RId');
        $grid->column('route_from_country', 'Country From');
        $grid->column('route_from_city', 'City From');
        $grid->column('route_to_country', 'Country To');
        $grid->column('route_to_city', 'City To');
        $grid->column('route_deadline', 'Deadline');

        $grid->column('exists_dispute', 'Dispute');

        return $grid;
    }
}