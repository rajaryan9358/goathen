<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



//Admin API

Route::post('get_transaction_total',[AdminController::class,'get_transaction_total']);
Route::post('login_admin',[AdminController::class,'login_admin']);
Route::post('get_users',[AdminController::class,'get_users']);
Route::post('block_unblock_user',[AdminController::class,'block_unblock_user']);
Route::post('get_user_transactions',[AdminController::class,'get_user_transactions']);
Route::post('get_user_chats',[AdminController::class,'get_user_chats']);
Route::post('get_user_ads',[AdminController::class,'get_user_ads']);
Route::post('get_chat_messages',[AdminController::class,'get_chat_messages']);
Route::get('get_user_ads_detail/{adsId}',[AdminController::class,'get_user_ads_detail']);
Route::post('get_user_subscriptions',[AdminController::class,'get_user_subscriptions']);
Route::post('cancel_subscription',[AdminController::class,'cancel_subscription']);
Route::post('extend_subscription',[AdminController::class,'extend_subscription']);
Route::post('get_overall_transactions',[AdminController::class,'get_overall_transactions']);
Route::post('get_notifications',[AdminController::class,'get_notifications']);
Route::post('create_notification',[AdminController::class,'create_notification']);
Route::get('get_banners',[AdminController::class,'get_banners']);
Route::post('create_banner',[AdminController::class,'create_banner']);
Route::post('update_banner',[AdminController::class,'update_banner']);
Route::get('delete_banner/{bannerId}',[AdminController::class,'delete_banner']);
Route::get('get_categories',[AdminController::class,'get_categories']);
Route::post('create_category',[AdminController::class,'create_category']);
Route::post('update_category',[AdminController::class,'update_category']);
Route::get('delete_category/{catId}',[AdminController::class,'delete_category']);
Route::post('get_all_ads',[AdminController::class,'get_all_ads']);
Route::get('delete_ads/{adsId}',[AdminController::class,'delete_ads']);
Route::post('delete_ads_photo',[AdminController::class,'delete_ads_photo']);
Route::get('get_app_settings',[AdminController::class,'get_app_settings']);
Route::post('change_app_settings',[AdminController::class,'change_app_settings']);
Route::get('hide_ads/{adsId}',[AdminController::class,'hide_ads']);


//User Api

Route::get('get_all_categories',[UserController::class,'get_all_categories']);
Route::post('get_category_ads',[UserController::class,'get_category_ads']);
Route::get('get_metadata/{userId}',[UserController::class,'get_metadata']);
Route::post('login_phone',[UserController::class,'login_phone']);
Route::post('login_email',[UserController::class,'login_email']);
Route::post('forgot_password',[UserController::class,'forgot_password']);
Route::post('reset_password',[UserController::class,'reset_password']);
Route::post('verify_email',[UserController::class,'verify_email']);
Route::post('send_phone_otp',[UserController::class,'send_phone_otp']);
Route::post('verify_phone_otp',[UserController::class,'verify_phone_otp']);
Route::post('create_user_account',[UserController::class,'create_user_account']);
Route::post('get_homepage',[UserController::class,'get_homepage']);
Route::post('get_more_ads',[UserController::class,'get_more_ads']);
Route::post('get_my_chats',[UserController::class,'get_my_chats']);
Route::post('get_my_ads',[UserController::class,'get_my_ads']);
Route::get('get_user_details/{userId}',[UserController::class,'get_user_details']);
Route::get('get_createads_data',[UserController::class,'get_createads_data']);
Route::post('create_ads',[UserController::class,'create_ads']);
Route::post('upload_ads_images',[UserController::class,'upload_ads_images']);
Route::get('get_ads_detail/{adsId}',[UserController::class,'get_ads_detail']);
Route::get('delete_ads/{adsId}',[UserController::class,'delete_ads']);
Route::get('mark_ads_completed/{adsId}',[UserController::class,'mark_ads_completed']);
Route::post('update_profile',[UserController::class,'update_profile']);
Route::post('get_transactions',[UserController::class,'get_transactions']);
Route::post('withdraw_to_bank',[UserController::class,'withdraw_to_bank']);

Route::get('get_bank_details/{userId}',[UserController::class,'get_bank_details']);
Route::get('get_user_subscription/{userId}',[UserController::class,'get_user_subscription']);
Route::post('create_cashfree_token',[UserController::class,'create_cashfree_token']);

Route::post('verify_cashfree_signature',[UserController::class,'verify_cashfree_signature']);
Route::get('get_information',[UserController::class,'get_information']);
Route::post('get_chat_messages',[UserController::class,'get_chat_messages']);
Route::post('send_message_user',[UserController::class,'send_message_user']);
Route::post('block_user_chat',[UserController::class,'block_user_chat']);