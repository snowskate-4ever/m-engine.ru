<?php

namespace App\Services\api;

use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Repositories\CatalogRepository;

class ApiService
{
    /**
     *  Задача не найдена
     */
    public const TASK_NOT_FOUND = 1100;

    /**
     *  Событие не найдено
     */
    public const EVENT_NOT_FOUND = 1101;

    /**
     *  Ресурс не найден
     */
    public const RESOURCE_NOT_FOUND = 1102;

    /**
     *  Тип не найден
     */
    public const TYPE_NOT_FOUND = 1103;
    
    public static function errorResponse(string $message, int $codError, $errors = [], int $code = 403)
    {
        $res = [
            'success' => false,
            'errors' => is_array($errors) ? (object)$errors : $errors,
            'data' => new \stdClass(),
            'codError' => $codError,
            'message' => $message,
        ];

        return Response::json($res, $code);   
    }
    
    public static function successResponse(string $message, $data = [])
    {
        $res = [
            'success' => true,
            'errors' => new \stdClass(),
            'data' => $data,
            'codError' => 0,
            'message' => $message,
        ];

        return Response::json($res, 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
