<?php

namespace App\Functions;

use App\Response\Status;

class GlobalFunction
{
    // SUCCESS
    public static function save($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::CREATED_STATUS
        );
    }

    public static function update_response($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::SUCESS_STATUS
        );
    }

    public static function login_user($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::SUCESS_STATUS
        );
    }

    public static function delete_response($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::SUCESS_STATUS
        );
    }

    public static function logout_response($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::SUCESS_STATUS
        );
    }

    public static function display_response($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::SUCESS_STATUS
        );
    }
    // ERRORS
    public static function not_found($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::DATA_NOT_FOUND
        );
    }

    public static function invalid($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::UNPROCESS_STATUS
        );
    }

    public static function invalid_category($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::UNPROCESS_STATUS
        );
    }

    public static function single_validation($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::SUCESS_STATUS
        );
    }

    public static function denied($message, $result = [])
    {
        return response()->json(
            [
                "message" => $message,
                "result" => $result,
            ],
            Status::DENIED_STATUS
        );
    }
}
