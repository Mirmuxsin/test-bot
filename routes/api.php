<?php

use App\Http\Controllers\BotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Milly\Laragram\FSM\FSM;
use Milly\Laragram\Types\Update;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::any('bot', function () {
    FSM::route('', [BotController::class, 'start'], [new Update()]);
    FSM::route('admin', [BotController::class, 'admin'], [new Update()]);
    FSM::route('login', [BotController::class, 'login'], [(new Update())->message]);
    FSM::route('test', [BotController::class, 'test'], [(new Update())->callback_query]);
    FSM::route('test', [BotController::class, 'test'], [(new Update())->message]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
