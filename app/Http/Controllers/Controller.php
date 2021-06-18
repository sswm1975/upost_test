<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidatorException;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Выполнение правил валидации и вызов исключения при ошибках.
     * В исключении возвращается стандартизированный ответ
     * @throws ValidatorException
     */
    public function returnValidated($validator)
    {
        if ($validator->fails()) {
            throw new ValidatorException($validator->errors()->all());
        }
    }
}
