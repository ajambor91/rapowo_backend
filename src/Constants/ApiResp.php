<?php

namespace App\Constants;
class ApiResp{
    const UNAUTHORIZED = 401;
    const NOT_FOUND = 404;
    const INTERNAL_ERROR = 500;
    const CONFLICT = 409;
    const BAD_REQUEST = 400;
    const ALL_OK = 200;

    const MSG_NOT_FOUND = 'not found';
    const MSG_NO_REQ_DATA = 'no required data';
    const MSG_EXISTS = 'exist';
    const MSG_UNAUTHORIZED = 'unathorized';
}
