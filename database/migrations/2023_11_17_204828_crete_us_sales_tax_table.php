<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreteUsSalesTaxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('us_sales_tax', function (Blueprint $table) {
            $table->char('code', 2)->primary()->comment('Код штату');
            $table->string('name_en', 20)->comment('Назва штату англійською');
            $table->string('name_uk', 20)->comment('Назва штату українською');
            $table->string('name_ru', 20)->comment('Назва штату російською');
            $table->decimal('tax_rate', 10, 2)->comment('Максимальна ставка податку з місцевим/міським податком на продаж');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Створено');
            $table->timestamp('updated_at')->nullable()->default(DB::raw('NULL ON UPDATE CURRENT_TIMESTAMP'))->comment('Змінено');
        });

        DB::statement("ALTER TABLE us_sales_tax COMMENT = 'Податок з продажу в США (взято з https://www.calculator.net/sales-tax-calculator.html)'");

        # Ставки податку з продажу для різних штатів взято з сайту https://www.calculator.net/sales-tax-calculator.html станом на 17.11.2023
        DB::table('us_sales_tax')->insert([
            ['code' => 'AL', 'name_en' => 'Alabama', 'name_uk' => 'Алабама', 'name_ru' => 'Алабама', 'tax_rate' => 13.5],
            ['code' => 'AK', 'name_en' => 'Alaska', 'name_uk' => 'Аляска', 'name_ru' => 'Аляска', 'tax_rate' => 7],
            ['code' => 'AZ', 'name_en' => 'Arizona', 'name_uk' => 'Арізона', 'name_ru' => 'Аризона', 'tax_rate' => 10.73],
            ['code' => 'AR', 'name_en' => 'Arkansas', 'name_uk' => 'Арканзас', 'name_ru' => 'Арканзас', 'tax_rate' => 11.63],
            ['code' => 'CA', 'name_en' => 'California', 'name_uk' => 'Каліфорнія', 'name_ru' => 'Калифорния', 'tax_rate' => 10.5],
            ['code' => 'CO', 'name_en' => 'Colorado', 'name_uk' => 'Колорадо', 'name_ru' => 'Колорадо', 'tax_rate' => 10],
            ['code' => 'CT', 'name_en' => 'Connecticut', 'name_uk' => 'Коннектикут', 'name_ru' => 'Коннектикут', 'tax_rate' => 6.35],
            ['code' => 'DE', 'name_en' => 'Delaware', 'name_uk' => 'Делавер', 'name_ru' => 'Делавэр', 'tax_rate' => 0],
            ['code' => 'DC', 'name_en' => 'District of Columbia', 'name_uk' => 'Район Колумбія', 'name_ru' => 'Округ Колумбия', 'tax_rate' => 6],
            ['code' => 'FL', 'name_en' => 'Florida', 'name_uk' => 'Флорида', 'name_ru' => 'Флорида', 'tax_rate' => 7.5],
            ['code' => 'GA', 'name_en' => 'Georgia', 'name_uk' => 'Джорджія', 'name_ru' => 'Джорджия', 'tax_rate' => 8],
            ['code' => 'GU', 'name_en' => 'Guam', 'name_uk' => 'Гуам', 'name_ru' => 'Гуам', 'tax_rate' => 4],
            ['code' => 'HI', 'name_en' => 'Hawaii', 'name_uk' => 'Гаваї', 'name_ru' => 'Гавайи', 'tax_rate' => 4.71],
            ['code' => 'ID', 'name_en' => 'Idaho', 'name_uk' => 'Айдахо', 'name_ru' => 'Айдахо', 'tax_rate' => 8.5],
            ['code' => 'IL', 'name_en' => 'Illinois', 'name_uk' => 'Іллінойс', 'name_ru' => 'Иллинойс', 'tax_rate' => 10.25],
            ['code' => 'IN', 'name_en' => 'Indiana', 'name_uk' => 'Індіана', 'name_ru' => 'Индиана', 'tax_rate' => 7],
            ['code' => 'IA', 'name_en' => 'Iowa', 'name_uk' => 'Айова', 'name_ru' => 'Айова', 'tax_rate' => 7],
            ['code' => 'KS', 'name_en' => 'Kansas', 'name_uk' => 'Канзас', 'name_ru' => 'Канзас', 'tax_rate' => 11.5],
            ['code' => 'KY', 'name_en' => 'Kentucky', 'name_uk' => 'Кентуккі', 'name_ru' => 'Кентукки', 'tax_rate' => 6],
            ['code' => 'LA', 'name_en' => 'Louisiana', 'name_uk' => 'Луїзіана', 'name_ru' => 'Луизиана', 'tax_rate' => 11.45],
            ['code' => 'ME', 'name_en' => 'Maine', 'name_uk' => 'Мен', 'name_ru' => 'Мэн', 'tax_rate' => 5.5],
            ['code' => 'MD', 'name_en' => 'Maryland', 'name_uk' => 'Меріленд', 'name_ru' => 'Мэриленд', 'tax_rate' => 6],
            ['code' => 'MA', 'name_en' => 'Massachusetts', 'name_uk' => 'Массачусетс', 'name_ru' => 'Массачусетс', 'tax_rate' => 6.25],
            ['code' => 'MI', 'name_en' => 'Michigan', 'name_uk' => 'Мічиган', 'name_ru' => 'Мичиган', 'tax_rate' => 6],
            ['code' => 'MN', 'name_en' => 'Minnesota', 'name_uk' => 'Міннесота', 'name_ru' => 'Миннесота', 'tax_rate' => 7.88],
            ['code' => 'MS', 'name_en' => 'Mississippi', 'name_uk' => 'Міссісіпі', 'name_ru' => 'Миссисипи', 'tax_rate' => 7.25],
            ['code' => 'MO', 'name_en' => 'Missouri', 'name_uk' => 'Міссурі', 'name_ru' => 'Миссури', 'tax_rate' => 10.85],
            ['code' => 'MT', 'name_en' => 'Montana', 'name_uk' => 'Монтана', 'name_ru' => 'Монтана', 'tax_rate' => 0],
            ['code' => 'NE', 'name_en' => 'Nebraska', 'name_uk' => 'Небраска', 'name_ru' => 'Небраска', 'tax_rate' => 7.5],
            ['code' => 'NV', 'name_en' => 'Nevada', 'name_uk' => 'Невада', 'name_ru' => 'Невада', 'tax_rate' => 8.25],
            ['code' => 'NH', 'name_en' => 'New Hampshire', 'name_uk' => 'Нью-Гемпшир', 'name_ru' => 'Нью-Гэмпшир', 'tax_rate' => 0],
            ['code' => 'NJ', 'name_en' => 'New Jersey', 'name_uk' => 'Нью-Джерсі', 'name_ru' => 'Нью-Джерси', 'tax_rate' => 12.63],
            ['code' => 'NM', 'name_en' => 'New Mexico', 'name_uk' => 'Нью-Мексико', 'name_ru' => 'Нью-Мексико', 'tax_rate' => 8.69],
            ['code' => 'NY', 'name_en' => 'New York', 'name_uk' => 'Нью-Йорк', 'name_ru' => 'Нью-Йорк', 'tax_rate' => 8.88],
            ['code' => 'NC', 'name_en' => 'North Carolina', 'name_uk' => 'Північна Кароліна', 'name_ru' => 'Северная Каролина', 'tax_rate' => 7.5],
            ['code' => 'ND', 'name_en' => 'North Dakota', 'name_uk' => 'Північна Дакота', 'name_ru' => 'Северная Дакота', 'tax_rate' => 8],
            ['code' => 'OH', 'name_en' => 'Ohio', 'name_uk' => 'Огайо', 'name_ru' => 'Огайо', 'tax_rate' => 8],
            ['code' => 'OK', 'name_en' => 'Oklahoma', 'name_uk' => 'Оклахома', 'name_ru' => 'Оклахома', 'tax_rate' => 11],
            ['code' => 'OR', 'name_en' => 'Oregon', 'name_uk' => 'Орегон', 'name_ru' => 'Орегон', 'tax_rate' => 0],
            ['code' => 'PA', 'name_en' => 'Pennsylvania', 'name_uk' => 'Пенсильванія', 'name_ru' => 'Пенсильвания', 'tax_rate' => 8],
            ['code' => 'PR', 'name_en' => 'Puerto Rico', 'name_uk' => 'Пуерто-Ріко', 'name_ru' => 'Пуэрто-Рико', 'tax_rate' => 11.5],
            ['code' => 'RI', 'name_en' => 'Rhode Island', 'name_uk' => 'Род-Айленд', 'name_ru' => 'Род-Айленд', 'tax_rate' => 7],
            ['code' => 'SC', 'name_en' => 'South Carolina', 'name_uk' => 'Південна Кароліна', 'name_ru' => 'Южная Каролина', 'tax_rate' => 9],
            ['code' => 'SD', 'name_en' => 'South Dakota', 'name_uk' => 'Південна Дакота', 'name_ru' => 'Южная Дакота', 'tax_rate' => 6],
            ['code' => 'TN', 'name_en' => 'Tennessee', 'name_uk' => 'Теннессі', 'name_ru' => 'Теннесси', 'tax_rate' => 9.75],
            ['code' => 'TX', 'name_en' => 'Texas', 'name_uk' => 'Техас', 'name_ru' => 'Техас', 'tax_rate' => 8.25],
            ['code' => 'UT', 'name_en' => 'Utah', 'name_uk' => 'Юта', 'name_ru' => 'Юта', 'tax_rate' => 8.35],
            ['code' => 'VT', 'name_en' => 'Vermont', 'name_uk' => 'Вермонт', 'name_ru' => 'Вермонт', 'tax_rate' => 7],
            ['code' => 'VA', 'name_en' => 'Virginia', 'name_uk' => 'Вірджинія', 'name_ru' => 'Виргиния', 'tax_rate' => 6],
            ['code' => 'WA', 'name_en' => 'Washington', 'name_uk' => 'Вашингтон', 'name_ru' => 'Вашингтон', 'tax_rate' => 10.4],
            ['code' => 'WV', 'name_en' => 'West Virginia', 'name_uk' => 'Західна Вірджинія', 'name_ru' => 'Западная Виргиния', 'tax_rate' => 7],
            ['code' => 'WI', 'name_en' => 'Wisconsin', 'name_uk' => 'Вісконсін', 'name_ru' => 'Висконсин', 'tax_rate' => 6.75],
            ['code' => 'WY', 'name_en' => 'Wyoming', 'name_uk' => 'Вайомінг', 'name_ru' => 'Вайоминг', 'tax_rate' => 6],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('us_sales_tax');
    }
}
