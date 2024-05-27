<?php

return [
    'login_successful'        => 'Login successful.',
    'logout_successful'       => 'Logout successful.',
    'auth_failed'             => 'These credentials do not match our records.',
    'register_successful'     => 'Register successful.',
    'user_not_found'          => 'User not found.',
    'old_password_incorrect'  => 'The current password is incorrect.',
    'token_incorrect'         => 'The token is incorrect.',
    'updated_successful'      => 'Updated successful.',
    'country_or_city_start_search'  => 'Start entering the name of the city or country. At least 2 letters.',
    'country_or_city_not_found'     => 'No results found. Check that the input is correct.',
    'country_not_found'       => 'Country not found.',
    'city_not_found'          => 'City not found.',
    'category_not_found'      => 'Category not found.',
    'not_filled_profile'      => 'In profile not filled name or surname or birthday.',
    'order_not_found'         => 'Order not found.',
    'order_exists'            => 'Order exists.',
    'route_not_found'         => 'Route not found.',
    'route_exists'            => 'Route exists.',
    'dispute_exists'          => 'Dispute exists.',
    'dispute_not_found'       => 'Dispute not found.',
    'rate_not_found'          => 'Rate not found.',
    'rate_add_double'         => 'Is double rate.',
    'who_start_incorrect'     => 'The field who_start incorrect.',
    'one_rate_per_order'      => 'Can be only one basic rate per order.',
    'three_rate_per_route'    => 'Сan be max three basic rate per route.',
    'differs_from_basic_rate' => 'The parameter differs from the basic rate.',
    'not_owner_basic_rate'    => 'You not owner basic rate.',
    'not_last_rate'           => 'Not last rate.',
    'rate_not_accepted'       => 'Rate not accepted.',
    'rate_accepted'           => 'Rate accepted.',
    'rate_exists_limit_user_price' => 'The amount of remuneration should not exceed the amount of remuneration indicated on the order.',
    'review_exists'           => 'You have already left a review',
    'review_not_allowed'      => 'You can not leave review for this job',
    'type_freelancer'         => 'Freelancer',
    'type_creator'            => 'Creator',
    'not_have_permission'     => 'You don\'t have permission.',
    'already_have_complaint'  => 'There is already a complaint from you.',
    'chat_not_found'          => 'Chat not found.',
    'exists_active_statement' => 'Exists active statement.',
    'statement_max_limit'     => 'Was created the maximum number of prolongations.',
    'deadline_not_arrived'    => 'The deadline has not arrived.',
    'statement_not_found'     => 'Statement not found.',
    'job_not_found'           => 'Job not found.',
    'image_not_found'         => 'Image not found.',
    'lock_add_message'        => 'It is forbidden to add a message.',
    'data_not_changed'        => 'The data has not changed.',
    'update_denied'           => 'Update denied.',
    'file_not_exists'         => 'The file does not exist: ',
    'start_and_end_points_match' => 'Start and end points match.',

    # Повідомлення нижче добавлені тільки в цей файл, в файли для UK та RU не добавлено
    'chat_closed' => 'Chat is closed.',
    'dispute_created' => 'The dispute has been successfully created.',
    'dispute_canceled' => 'The dispute has been successfully canceled.',
    'image_uploaded' => 'The image has been successfully uploaded.',
    'message_created' => 'The message has been successfully created.',
    'notification_read' => 'The notification has been read.',
    'order_created' => 'The order has been successfully created.',
    'order_updated' => 'The order has been successfully updated.',
    'order_closed' => 'The order is closed.',
    'order_complaint_created' => 'An order complaint has been created.',
    'profile_updated' => 'The profile has been successfully updated.',
    'language_updated' => 'The language has been successfully updated.',
    'rate_created' => 'The bet has been successfully created.',
    'rate_updated' => 'The bet has been successfully updated.',
    'review_created' => 'The review has been successfully created.',
    'route_created' => 'The route has been successfully created.',
    'route_updated' => 'The bet has been successfully updated.',
    'route_closed' => 'The route is closed.',
    'statement_created' => 'The statement has been successfully created.',
    'statement_rejected' => 'The statement has been rejected.',
    'statement_accepted' => 'The statement has been accepted.',
    'file_uploaded' => 'The file has been uploaded.',
    'withdrawal_created' => 'Request for withdrawal of money have been created.',
    # Кінець нових повідомлень

    'wallet' => [
        'not_enough_funds' => 'There are not enough funds.',
        'exists_unfinished_withdrawals' => 'There is an unfinished withdrawal request.',
     ],


    'verification_code' => [
        'incorrect'         => 'Verification code is incorrect',
        'send_error'        => 'Error sending verification code',
        'send_by_email'     => 'Verification code sent to address',
        'change_successful' => 'Data updated successfully',
    ],

    # Messages for mailing letters
    'email' => [
        'social_registration' => 'Registration through the social network',
        'send_success'        => 'The message has been sent, you will be contacted shortly.',
        'send_error'          => 'There was an error sending your message.',
    ],

    # User attributes
    'user' => [
        'genders' => [
            'male'    => 'Male',
            'female'  => 'Female',
            'unknown' => 'Unknown',
        ],
        'validations' => [
            'valid'    => 'Verified',
            'no_valid' => 'Not verified',
        ],
        'statuses' => [
            'active'     => 'Active',
            'not_active' => 'Not active',
            'banned'     => 'Banned',
            'removed'    => 'Removed',
        ],
    ],

    # Order attributes
    'order' => [
        'statuses' => [
            'active'     => 'Active',
            'in_work'    => 'In work',
            'closed'     => 'Closed',
            'failed'     => 'Failed',
            'successful' => 'Successful',
            'banned'     => 'Banned',
        ],
    ],

    # Route attributes
    'route' => [
        'statuses' => [
            'active'     => 'Active',
            'in_work'    => 'In work',
            'closed'     => 'Closed',
            'successful' => 'Successful',
            'banned'     => 'Banned',
        ],
    ],

    # Rate attributes
    'rate' => [
        'statuses' => [
            'active'     => 'Active',
            'canceled'   => 'Canceled',
            'rejected'   => 'Rejected',
            'accepted'   => 'Accepted',
            'buyed'      => 'Buyed',
            'successful' => 'Successful',
            'done'       => 'Done',
            'failed'     => 'Failed',
            'banned'     => 'Banned',
        ],
    ],

    # Chat attributes
    'chat' => [
        'statuses' => [
            'active'     => 'Active',
            'closed'     => 'Closed',
        ],
    ],

    # Dispute attributes
    'dispute' => [
        'statuses' => [
            'active'    => 'Active',
            'appointed' => 'Appointed',
            'in_work'   => 'In work',
            'closed'    => 'Closed',
            'canceled'  => 'Canceled',
        ],
    ],

    # Payment attributes
    'payment' => [
        'statuses' => [
            'new'       => 'New',
            'appointed' => 'Appointed',
            'rejected'  => 'Rejected',
            'done'      => 'Done',
        ],
        'types' => [
            'reward' => 'Reward',
            'refund' => 'Refund',
        ],
    ],

    # Transaction attributes
    'transaction' => [
        'statuses' => [
            'new'       => 'New',
            'appointed' => 'Appointed',
            'rejected'  => 'Rejected',
            'done'      => 'Done',
            'created'   => 'Created',
            'approved'  => 'Approved',
            'canceled'  => 'Canceled',
            'failed'    => 'Failed',
            'payed'     => 'Payed',
        ],
        'types' => [
            'payment' => 'Order payment',
            'reward' => 'Reward',
            'refund' => 'Refund',
        ],
    ],

    # Withdrawal attributes
    'withdrawal' => [
        'statuses' => [
            'new'         => 'New',
            'done'        => 'Done',
            'in_progress' => 'In progress',
            'fail'        => 'Fail',
            'expired'     => 'Expired',
        ],
    ],
];
