<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'messages' => [
        'accepted' => 'The :attribute must be accepted',

        'accepted' => 'the_:attribute_must_be_accepted',
        'active_url' => 'the_:attribute_is_not_a_valid_url',
        'after' => 'the_:attribute_must_be_a_date_after_:date',
        'after_or_equal' => 'the_:attribute_must_be_a_date_after_or_equal_to_:date',
        'alpha' => 'the_:attribute_may_only_contain_letters',
        'alpha_dash' => 'the_:attribute_may_only_contain_letters,_numbers,_dashes_and_underscores',
        'alpha_num' => 'the_:attribute_may_only_contain_letters_and_numbers',
        'array' => 'the_:attribute_must_be_an_array',
        'before' => 'the_:attribute_must_be_a_date_before_:date',
        'before_or_equal' => 'the_:attribute_must_be_a_date_before_or_equal_to_:date',
        'between' => [
            'numeric' => 'the_:attribute_must_be_between_:min_and_:max',
            'file' => 'the_:attribute_must_be_between_:min_and_:max_kilobytes',
            'string' => 'the_:attribute_must_be_between_:min_and_:max_characters',
            'array' => 'the_:attribute_must_have_between_:min_and_:max_items',
        ],
        'boolean' => 'the_:attribute_field_must_be_true_or_false',
        'confirmed' => 'the_:attribute_confirmation_does_not_match',
        'date' => 'the_:attribute_is_not_a_valid_date',
        'date_equals' => 'the_:attribute_must_be_a_date_equal_to_:date',
        'date_format' => 'the_:attribute_does_not_match_the_format_:format',
        'different' => 'the_:attribute_and_:other_must_be_different',
        'digits' => 'the_:attribute_must_be_:digits_digits',
        'digits_between' => 'the_:attribute_must_be_between_:min_and_:max_digits',
        'dimensions' => 'the_:attribute_has_invalid_image_dimensions',
        'distinct' => 'the_:attribute_field_has_a_duplicate_value',
        'email' => 'the_:attribute_must_be_a_valid_email_address',
        'ends_with' => 'the_:attribute_must_end_with_one_of_the_following:_:values',
        'exists' => 'the_selected_:attribute_is_invalid',
        'file' => 'the_:attribute_must_be_a_file',
        'filled' => 'the_:attribute_field_must_have_a_value',
        'gt' => [
            'numeric' => 'the_:attribute_must_be_greater_than_:value',
            'file' => 'the_:attribute_must_be_greater_than_:value_kilobytes',
            'string' => 'the_:attribute_must_be_greater_than_:value_characters',
            'array' => 'the_:attribute_must_have_more_than_:value_items',
        ],
        'gte' => [
            'numeric' => 'the_:attribute_must_be_greater_than_or_equal_:value',
            'file' => 'the_:attribute_must_be_greater_than_or_equal_:value_kilobytes',
            'string' => 'the_:attribute_must_be_greater_than_or_equal_:value_characters',
            'array' => 'the_:attribute_must_have_:value_items_or_more',
        ],
        'image' => 'the_:attribute_must_be_an_image',
        'in' => 'the_selected_:attribute_is_invalid',
        'in_array' => 'the_:attribute_field_does_not_exist_in_:other',
        'integer' => 'the_:attribute_must_be_an_integer',
        'ip' => 'the_:attribute_must_be_a_valid_ip_address',
        'ipv4' => 'the_:attribute_must_be_a_valid_ipv4_address',
        'ipv6' => 'the_:attribute_must_be_a_valid_ipv6_address',
        'json' => 'the_:attribute_must_be_a_valid_json_string',
        'lt' => [
            'numeric' => 'the_:attribute_must_be_less_than_:value',
            'file' => 'the_:attribute_must_be_less_than_:value_kilobytes',
            'string' => 'the_:attribute_must_be_less_than_:value_characters',
            'array' => 'the_:attribute_must_have_less_than_:value_items',
        ],
        'lte' => [
            'numeric' => 'the_:attribute_must_be_less_than_or_equal_:value',
            'file' => 'the_:attribute_must_be_less_than_or_equal_:value_kilobytes',
            'string' => 'the_:attribute_must_be_less_than_or_equal_:value_characters',
            'array' => 'the_:attribute_must_not_have_more_than_:value_items',
        ],
        'max' => [
            'numeric' => 'the_:attribute_may_not_be_greater_than_:max',
            'file' => 'the_:attribute_may_not_be_greater_than_:max_kilobytes',
            'string' => 'the_:attribute_may_not_be_greater_than_:max_characters',
            'array' => 'the_:attribute_may_not_have_more_than_:max_items',
        ],

        'mimes' => 'the_:attribute_must_be_a_file_of_type:_:values',
        'mimetypes' => 'the_:attribute_must_be_a_file_of_type:_:values',
        'min' => [
            'numeric' => 'the_:attribute_must_be_at_least_:min',
            'file' => 'the_:attribute_must_be_at_least_:min_kilobytes',
            'string' => 'the_:attribute_must_be_at_least_:min_characters',
            'array' => 'the_:attribute_must_have_at_least_:min_items',
        ],
        'not_in' => 'the_selected_:attribute_is_invalid',
        'not_regex' => 'the_:attribute_format_is_invalid',
        'numeric' => 'the_:attribute_must_be_a_number',
        'password' => 'the_password_is_incorrect',
        'present' => 'the_:attribute_field_must_be_present',
        'regex' => 'the_:attribute_format_is_invalid',
        'required' => 'the_:attribute_field_is_required',
        'required_if' => 'the_:attribute_field_is_required_when_:other_is_:value',
        'required_unless' => 'the_:attribute_field_is_required_unless_:other_is_in_:values',
        'required_with' => 'the_:attribute_field_is_required_when_:values_is_present',
        'required_with_all' => 'the_:attribute_field_is_required_when_:values_are_present',
        'required_without' => 'the_:attribute_field_is_required_when_:values_is_not_present',
        'required_without_all' => 'the_:attribute_field_is_required_when_none_of_:values_are_present',
        'same' => 'the_:attribute_and_:other_must_match',
        'size' => [
            'numeric' => 'the_:attribute_must_be_:size',
            'file' => 'the_:attribute_must_be_:size_kilobytes',
            'string' => 'the_:attribute_must_be_:size_characters',
            'array' => 'the_:attribute_must_contain_:size_items',
        ],
        'starts_with' => 'the_:attribute_must_start_with_one_of_the_following:_:values',
        'string' => 'the_:attribute_must_be_a_string',
        'timezone' => 'the_:attribute_must_be_a_valid_zone',
        'unique' => 'the_:attribute_has_already_been_taken',
        'uploaded' => 'the_:attribute_failed_to_upload',
        'url' => 'the_:attribute_format_is_invalid',
        'uuid' => 'the_:attribute_must_be_a_valid_uuid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'id'                   => 'id',
        'lang'                 => 'lang',
        'show'                 => 'show',
        'page'                 => 'page',
        'status'               => 'status',
        'sorting'              => 'sorting',
        'sort_by'              => 'sort_by',
        'currency'             => 'currency',
        'post_type'            => 'post_type',
        'category_id'          => 'category_id',
        'base64_image'         => 'not_valid_image',

        'user_id'              => 'user_id',
        'user_phone'           => 'user_phone',
        'user_email'           => 'user_email',
        'user_password'        => 'user_password',

        'date_to'              => 'date_to',
        'date_from'            => 'date_from',
        'city_id'              => 'city_id',
        'city_to'              => 'city_to',
        'city_from'            => 'city_from',
        'price_to'             => 'price_to',
        'price_from'           => 'price_from',
        'country_id'           => 'country_id',
        'country_to'           => 'country_to',
        'country_from'         => 'country_from',

        'order_id'             => 'order_id',
        'order_name'           => 'order_name',
        'order_category'       => 'order_category',
        'order_price'          => 'order_price',
        'order_price_usd'      => 'order_price_usd',
        'order_currency'       => 'order_currency',
        'order_count'          => 'order_count',
        'order_size'           => 'order_size',
        'order_weight'         => 'order_weight',
        'order_product_link'   => 'order_product_link',
        'order_text'           => 'order_text',
        'order_images'         => 'order_images',
        'order_from_country'   => 'order_from_country',
        'order_from_city'      => 'order_from_city',
        'order_from_address'   => 'order_from_address',
        'order_to_country'     => 'order_to_country',
        'order_to_city'        => 'order_to_city',
        'order_to_address'     => 'order_to_address',
        'order_start'          => 'order_start',
        'order_deadline'       => 'order_deadline',
        'order_personal_price' => 'order_personal_price',
        'order_user_price'     => 'order_user_price',
        'order_user_currency'  => 'order_user_currency',
        'order_not_more_price' => 'order_not_more_price',
    ],
];
