<?php

namespace App\Platform\Controllers\Old;

use App\Events\MessagesCounterUpdate;
use App\Models\Chat;
use App\Models\Message;
use App\Platform\Controllers\AdminController;
use App\Platform\Controllers\ChatMessage;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                chats.lock_status,
                chats.created_at,
                chats.updated_at,

                IFNULL((SELECT COUNT(1) FROM messages WHERE chat_id = chats.id), 0) AS messages_cnt,

                chats.customer_id,
                TRIM(CONCAT(uc.`surname`, ' ', uc.`name`)) AS customer_name,
                uc.phone AS customer_phone,
                uc.email AS customer_email,
                chats.customer_unread_count,

                chats.order_id,
                o.`name` AS order_name,
                o.`description` AS order_description,
                o.product_link AS order_url,
                o.price AS order_price,
                o.price_usd AS order_price_usd,
                o.currency AS order_currency,
                o.products_count AS order_products_count,
                o.user_price_usd AS order_profit_usd,
                o.deduction_usd AS order_deduction_usd,
                o.status AS order_status,
                o.deadline AS order_deadline,
                o.images AS order_images,

                chats.performer_id,
                TRIM(CONCAT(up.`surname`, ' ', up.`name`)) AS performer_name,
                up.phone AS performer_phone,
                up.email AS performer_email,
                chats.performer_unread_count,

                chats.route_id,
                cntfr.name_ru AS route_from_country,
                cfr.name_ru AS route_from_city,
                cnttr.name_ru AS route_to_country,
                ctr.name_ru AS route_to_city,
                r.deadline AS route_deadline,

                IFNULL(d.id, 0) AS exists_dispute,
                IFNULL(d.unread_messages_count, 0) AS unread_messages_count,
                last_mess.user_id AS last_message_user_id
            ")
            ->join('users as uc','uc.id', 'chats.customer_id')
            ->join('users as up','up.id', 'chats.performer_id')
            ->join('orders AS o','o.id', 'chats.order_id')
            ->join('routes AS r','r.id', 'chats.route_id')
            ->join('countries AS cntfr','cntfr.id', 'r.from_country_id')
            ->join('countries AS cnttr','cnttr.id', 'r.to_country_id')
            ->leftJoin('cities AS cfr','cfr.id', 'r.from_city_id')
            ->leftJoin('cities AS ctr','ctr.id', 'r.to_city_id')
            ->leftJoin('disputes AS d', 'd.chat_id', 'chats.id')
            ->leftJoin(
                DB::raw(
               '(
                        SELECT m1.chat_id, m1.user_id
                        FROM messages m1
                        LEFT JOIN messages m2 ON (m1.chat_id = m2.chat_id AND m1.id < m2.id)
                        WHERE m2.id IS NULL
                    ) AS last_mess'
                ), 'last_mess.chat_id', '=', 'chats.id'
            );

        # COLORS ROW GRID
        $grid->rows(function (Grid\Row $row) {
            if ($row->exists_dispute) {
                $row->setAttributes(['class' => 'danger']);
            }
        });

        # COLUMNS
        $grid->column('id', )->sortable();
        $grid->column('status')->showOtherField('status_name')->sortable();
        $grid->column('lock_status', 'Chat`s lock status')
            ->editable('select', Chat::LOCK_STATUSES)
            ->sortable();

        $grid->column('created_at')->sortable();
        $grid->column('updated_at')->sortable();
        $grid->column('messages_cnt')
            ->ajaxModal(ChatMessage::class, 700)
            ->setAttributes(['align' => 'center'])
            ->sortable();

        $grid->column('unread_messages_count', 'Unread')
            ->display(function ($count) {
                $empty = $this->exists_dispute && $this->last_message_user_id > 0 ? "<span class='label label-default'>Прочитано, без ответа</span>" : '';
                return $count ? "<span class='label label-danger'>$count</span>" :  $empty;
            })
            ->setAttributes(['align'=>'center'])
            ->help('Количество непрочитанных сообщений менеджером при наличии спора');

        $grid->column('customer_id', 'CId')->sortable();
        $grid->column('customer_name', 'Customer');
        $grid->column('customer_phone', 'Customer phone');
        $grid->column('customer_email', 'Customer email');

        $grid->column('order_id', 'OId')->sortable();
        $grid->column('order_name', 'Order');
        $grid->column('order_price', 'Price')->setAttributes(['align'=>'right']);
        $grid->column('order_currency', '#');
        $grid->column('order_profit_usd', 'Profit')->setAttributes(['align'=>'right']);
        $grid->column('order_profit_currency', '#')->default('$');
        $grid->column('order_deduction_usd', 'Tax & Fee')->setAttributes(['align'=>'right']);

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

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new Chat);
        $form->select('lock_status')->options(Chat::LOCK_STATUSES);
        return $form;
    }

    /**
     * Добавить сообщение.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addMessage(Request $request): JsonResponse
    {
        $chat = Chat::find($request->get('chat_id'));

        Message::create($request->all());

        $chat->customer_unread_count = $chat->customer_unread_count + 1;
        $chat->performer_unread_count = $chat->performer_unread_count + 1;
        $chat->save();

        $this->broadcastCountUnreadMessages($chat->customer_id, $chat->customer_unread_count);
        $this->broadcastCountUnreadMessages($chat->performer_id, $chat->performer_unread_count);

        return response()->json([
            'status' => true,
            'lock_status' => $chat->lock_status,
        ]);
    }

    /**
     * Броадкастим количество непрочитанных сообщений.
     *
     * @param int $recipient_id
     * @param int $unread_messages
     */
    private function broadcastCountUnreadMessages(int $recipient_id, int $unread_messages)
    {
        try {
            broadcast(new MessagesCounterUpdate([
                'user_id'         => $recipient_id,
                'unread_messages' => $unread_messages,
            ]));
        } catch (\Exception $e) {

        }
    }
}
