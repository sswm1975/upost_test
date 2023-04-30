$.fn.dataTable.moment( 'DD.MM.YYYY' );
$.fn.dataTable.moment( 'DD.MM.YYYY' );
$.fn.dataTable.moment( 'DD.MM.YYYY' );

var columns = [
    { data: 'id', className: 'dt-body-center', searchBuilderTitle: 'Код' },
    { data: 'status', searchBuilderTitle: 'Статус' },
    { data: 'user_id', className: 'dt-body-center', searchBuilderTitle: 'Код заказчика' },
    { data: 'user_full_name', searchBuilderTitle: 'ФИО заказчика' },
    { data: 'from_country_id', searchBuilderTitle: 'Откуда Код страны' },
    { data: 'from_country_name', searchBuilderTitle: 'Откуда Страна' },
    { data: 'from_city_id', className: 'dt-body-center', searchBuilderTitle: 'Откуда Код города' },
    { data: 'from_city_name', searchBuilderTitle: 'Откуда Город' },
    { data: 'to_country_id', className: 'dt-body-center', searchBuilderTitle: 'Куда Код страны' },
    { data: 'to_country_name', searchBuilderTitle: 'Куда Страна' },
    { data: 'to_city_id', className: 'dt-body-center', searchBuilderTitle: 'Куда Код города' },
    { data: 'to_city_name', searchBuilderTitle: 'Куда город' },
    { data: 'price', className: 'dt-body-right', searchBuilderTitle: 'Цена' },
    { data: 'currency', className: 'dt-body-center', searchBuilderTitle: 'Валюта' },
    { data: 'price_usd', className: 'dt-body-right', searchBuilderTitle: 'Цена $' },
    { data: 'user_price_usd', className: 'dt-body-right', searchBuilderTitle: 'Цена ком.' },
    { data: 'products_count', className: 'dt-body-center', searchBuilderTitle: 'Кол-во' },
    { data: 'deadline', className: 'dt-body-center', searchBuilderTitle: 'Дата доставки' },
    { data: 'created_at', className: 'dt-body-center', searchBuilderTitle: 'Дата создания' },
    { data: 'updated_at', className: 'dt-body-center', searchBuilderTitle: 'Дата изменения' },
    { data: 'strikes', searchBuilderTitle: 'Жалобы' },
];
