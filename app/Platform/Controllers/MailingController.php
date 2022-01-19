<?php

namespace App\Platform\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\OrderBanEmail;
use App\Mail\SendTokenUserDataChange;
use App\Mail\SocialChangePassword;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Encore\Admin\Facades\Admin;
use Faker\Factory as Faker;

class MailingController extends Controller
{
    const LOCALES = [
        'uk' => 'Uk_UA',
        'ru' => 'Ru_RU',
        'en' => 'GB_GB',
    ];

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content): Content
    {
        # Контент письма: вместо 100% устанавливаем авто-размер, что приводит к выравниванию письма к левому краю.
        Admin::style('.wrapper {width:auto !important;}');

        $mailings = static::getMailings();
        $mailing = Arr::last(request()->segments());

        if (!Arr::exists($mailings, $mailing)) {
            return $content->withError('Ошибка', 'Рассылка не найдена!');
        }
        $title = $mailings[$mailing]['title'];
        $icon = $mailings[$mailing]['icon'];
        $lang = request('lang', 'uk');

        return $content
            ->title("<i class='fa {$icon}'></i>&nbsp;{$title}")
            ->description('рассылка')
            ->breadcrumb(
                ['text' => 'Рассылки', 'icon' => 'envelope-o'],
                ['text' => $mailings[$mailing]['title'], 'icon' => str_replace('fa-', '', $icon)],
            )
            ->row($this->menu($mailing))
            ->body(static::$mailing($lang));
    }

    /**
     * Из пункта меню "Рассылки" получаем список всех подпунктов.
     *
     * @return array
     */
    private static function getMailings(): array
    {
        $mailings = [];
        $items = collect(Admin::menu())->keyBy('uri')->get('mailings')['children'];
        foreach ($items as $item) {
            $key = str_replace('mailings/', '',  $item['uri']);
            $mailings[$key] = [
                'title' => $item['title'],
                'icon'  => $item['icon'],
            ];
        }
        return $mailings;
    }

    /**
     * Формируем список меню с выбором языка.
     *
     * @param string $mailing
     * @return View
     */
    public function menu(string $mailing = ''): View
    {
        return view('platform.emails.menu')->with([
            'mailing'   => $mailing,
            'languages' => ADMIN_LANGUAGES,
        ]);
    }

    /**
     * Письмо: Смена пароля при регистрации через социальную сеть.
     *
     * @param string $lang
     * @return SocialChangePassword
     */
    private static function social_change_password(string $lang): SocialChangePassword
    {
        $faker = Faker::create(self::LOCALES[$lang]);
        $gender = $faker->randomElement(['male', 'female']);

        return new SocialChangePassword([
            'language'    => $lang,
            'provider'    => $faker->randomElement(['google', 'facebook']),
            'client_name' => $faker->name($gender),
            'email'       => $faker->unique()->email,
            'password'    => Str::random(10),
            'url'         => env('WORDPRESS_URL') . 'log-in/?change_password',
        ]);
    }

    /**
     * Письмо: Много жалоб по заказу.
     *
     * @param string $lang
     * @return OrderBanEmail
     */
    private static function order_ban(string $lang): OrderBanEmail
    {
        return new OrderBanEmail($lang);
    }

    /**
     * Письмо: Код подтверждения смены данных профиля.
     *
     * @param string $lang
     * @return SendTokenUserDataChange
     */
    private static function send_token_user_data_change(string $lang): SendTokenUserDataChange
    {
        return new SendTokenUserDataChange(Str::random(8), $lang);
    }
}