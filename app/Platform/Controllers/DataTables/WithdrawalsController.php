<?php

namespace App\Platform\Controllers\DataTables;

use App\Models\Withdrawal;
use App\Models\WithdrawalFile;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class WithdrawalsController extends BaseController
{
    const FOLDER_CSV_FILES = '/payment-requests/';

    protected string $title = 'Заявки на вывод денег';
    protected string $icon = 'fa-money';
    protected string $entity = 'withdrawals';
    protected int $count_columns = 10;

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $statuses = [];
        foreach (Withdrawal::STATUSES as $status) {
            $statuses[$status] = __("message.withdrawal.statuses.$status");
        }
        $statuses['all'] =  'Все';

        return compact('statuses');
    }

    /**
     * Получить данные для таблицы.
     *
     * @return array
     */
    public function getData()
    {
        $status = request('status', Withdrawal::STATUS_NEW);

        $data = Withdrawal::with(['user', 'file'])
            ->when($status != 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'status' => $row->status,
                    'user_id' => $row->user->id,
                    'user_full_name' => $row->user->full_name,
                    'amount' => $row->amount,
                    'email' => $row->email,
                    'file_id' => $row->file_id,
                    'file_name' => $row->file->name ?? '',
                    'created_at' => $row->created_at->format('d.m.Y h:i:s'),
                    'updated_at' => $row->updated_at ? $row->updated_at->format('d.m.Y h:i:s') : '',
                ];
            })
            ->all();

        return compact('data');
    }

    /**
     * Создать CSV-файл по выплате денег.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createCSVFile(Request $request)
    {
        if ($request->isNotFilled('ids')) {
            return jsonResponse('Не заполнены обязательные параметры!', false);
        }

        $ids = json_decode($request->input('ids'));
        $rows = Withdrawal::with('user:id,name,surname')->whereKey($ids)->where('status', Withdrawal::STATUS_NEW)->get();

        $path = storage_path() . self::FOLDER_CSV_FILES;
        if (! File::exists($path)) {
            File::makeDirectory($path);
        }

        $file = WithdrawalFile::create();

        # Формування ім'я файлу по шаблону: номер файлу + дата з часом + кількості заявок + сума.
        # Наприклад: id-55_date-01-08-2023-requests-455+amount-1550-USD.csv.
        $file->name = sprintf('withdrawals_id-%d_date-%s_quantity-%d_amount-%s-usd.csv',
            $file->id,
            $file->created_at->format('Ymdhis'),
            $rows->count(),
            $rows->sum('amount'),
        );
        $file->save();
        $full_filename = $path . $file->name;
        $handle = fopen($full_filename, 'w');
        fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); # add BOM to fix UTF-8 in Excel
        fputcsv($handle, ["name", "recipientEmail", "paymentReference", "amountCurrency", "amount", "sourceCurrency", "targetCurrency", "type"], ',');
        foreach ($rows as $row) {
            fputs($handle, implode(",", array_map("encodeFunc", [
                $row->user->full_name,
                $row->email,
                $row->id,
                'USD',
                $row->amount,
                'USD',
                'USD',
                'EMAIL'
            ]))."\r\n");
        }
        fclose($handle);

        Withdrawal::whereKey($ids)->where('status', Withdrawal::STATUS_NEW)->update([
            'file_id' => $file->id,
            'status' => Withdrawal::STATUS_IN_PROGRESS,
            'updated_at' => Carbon::now(),
        ]);

        return jsonResponse('Файл создан, заявки перешли в статус "В работе".');
    }

    /**
     * Скачать CSV-файл.
     *
     * @param int $id
     */
    public function downloadCSVFile(int $id)
    {
        $filename = WithdrawalFile::whereKey($id)->value('name') ?? '';

        $full_filename = storage_path() . self::FOLDER_CSV_FILES . $filename;
        if (File::missing($full_filename)) {
            die('File not found !');
        }

        return response()->download($full_filename, $filename, ["Content-type" => "text/csv"]);
    }
}
