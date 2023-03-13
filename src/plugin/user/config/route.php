<?php

use plugin\user\app\controller\CaptchaController;
use plugin\user\app\controller\IndexController;
use plugin\user\app\controller\LoginController;
use Webman\Route;

Route::any('/app/user/logout', [LoginController::class, 'logout']);

Route::any('/app/user/save', [IndexController::class, 'save']);
Route::any('/app/user/avatar', [IndexController::class, 'avatar']);
Route::any('/app/user/password', [IndexController::class, 'password']);
Route::any('/app/user/email', [IndexController::class, 'email']);
Route::any('/app/user/mobile', [IndexController::class, 'mobile']);

Route::any('/app/user/captcha/image/{type}', [CaptchaController::class, 'image']);
Route::any('/app/user/captcha/email/{type}', [CaptchaController::class, 'email']);
Route::any('/app/user/captcha/mobile/{type}', [CaptchaController::class, 'mobile']);