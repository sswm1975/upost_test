<?php

namespace App\Platform\Controllers;

use App\Models\Feedback;

use App\Platform\Extensions\Tools\SetRead;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FeedbackController extends AdminController
{
    protected string $title = 'Обратная связь';
    protected string $icon = 'fa-comments-o';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Feedback);

        $grid->disableRowSelector(false);
        $grid->disablePagination(false);
        $grid->disableColumnSelector(false);
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->paginate(20);

        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->add('Установить "Прочитано"', new SetRead());
            });
        });

        $grid->column('id', 'Код')->sortable();
        $grid->column('subject', 'Раздел')->sortable();
        $grid->column('name', 'Клиент')->sortable();
        $grid->column('phone', 'Телефон')->sortable();
        $grid->column('email', 'Емейл')->sortable();
        $grid->column('text', 'Текст');
        $grid->column('created_at', 'Создано')->sortable();
        $grid->column('updated_at', 'Изменено')->hide()->sortable();
        $grid->column('read_at', 'Прочитано')->sortable();
        $grid->column('read_admin_user.username', 'Кто прочитал');

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id): Show
    {
        return $this->showFields(Feedback::findOrFail($id));
    }

    /**
     * Set read for letters (batch action).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setRead(Request $request): JsonResponse
    {
        $ids = explode(',', $request->get('ids'));

        $affected_rows = Feedback::whereKey($ids)
            ->whereNull('read_admin_user_id')
            ->update([
                'read_at'            => Carbon::now(),
                'read_admin_user_id' => Admin::user()->id,
            ]);

        return response()->json([
            'status'        => $affected_rows > 0,
            'affected_rows' => $affected_rows,
        ]) ;
    }
}
