<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\CurrencyRate;
use App\Models\Currency;

class FillCurrencyRates extends Command
{
    const API_KEY = 'DgBOGc50nTMmiMducCULojrVdjKluS28';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fill:currency_rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновить курсы валют';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
    	# изредка появляется ошибка: 8 - Undefined index: REMOTE_ADDR
    	if ( !array_key_exists('REMOTE_ADDR', $_SERVER) ) {
    		$_SERVER["REMOTE_ADDR"] = '127.0.0.1';
    	}

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->showInfo('Обновление курсов: ' . $this->updateRates());
    }

    /**
     * Обновить курсы.
     *
     * @return string
     */
    public function updateRates(): string
    {
        $now = date('Y-m-d');
        $max_date = CurrencyRate::getMaxDate4Rate();

        # если за сегодня уже курсы есть, то выходим
        if ($max_date == $now) {
            return "курсы за дату {$now} уже загружены";
        }

        $response = $this->getRatesFromService();
        $json = $response['content'];

        if ($response['errno'] || empty($json)) {
            return sprintf('сбой при HTTP-запросе, ошибка: %s - %s', $response['errno'], $response['error']);
        }

        $data = json_decode($json, true);
        $rates = [];
        foreach ($data['rates'] as $symbol => $rate) {
            $currency_id = getCurrencySymbol($symbol);
            $rates[] = [
                'date'        => $data['date'],
                'currency_id' => $currency_id,
                'rate'        => (float)$rate,
                'created_at'  => Carbon::now(),
            ];

            Currency::whereKey($currency_id)->update([
                'rate'       => (float)$rate,
                'updated_at' => Carbon::now(),
            ]);
        }

        if (empty($rates)) {
            return "курсы не обновлены";
        }
        CurrencyRate::insert($rates);
        Currency::updateCache();

        $info = collect($data['rates'])
            ->map(function($rate, $key) {
                return "$key=$rate";
            })->implode(', ');

        return "загружены курсы на дату {$data['date']}: {$info}";
    }

    /**
     * Вывести сообщение.
     *
     * @param string $message
     * @return void
     */
    private function showInfo(string $message = '')
    {
        if ($message) {
            $this->info('[' . Carbon::now()->format('d.m.Y H:i:s') . '] ' . $message);
        } else {
            $this->info('');
        }
    }

    /**
     * Выполнить CURL-запрос.
     * Сервис предложил Глеб, см. https://fixer.io, https://apilayer.com/marketplace/fixer-api#pricing
     *
     * @return array
     */
    private function getRatesFromService(): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.apilayer.com/fixer/latest?symbols=UAH,EUR,RUB&base=USD",
            CURLOPT_HTTPHEADER => [
                "Content-Type: text/plain",
                "apikey: " . static::API_KEY,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET"
        ]);

        $content = curl_exec($curl);

        $errno = curl_errno($curl);
        $error = curl_error($curl);
        curl_close($curl);

        return [
            'content' => $content,
            'errno'   => $errno,
            'error'   => $error,
        ];
    }
}
