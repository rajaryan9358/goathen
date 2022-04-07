<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\AdvertisementImage;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\File; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{


//Admin app api

//1. login admin using id and password
//2. get & search users sort by (name, balance, new first, subscription)
//3. get user transactions
//4. get user chats
//5. get user ads
//6. get chat messages
//7. get ads details
//8. get subscriptions
//9. cancel subscriptions
//10. extend subscriptions
//11. get overall transactions (withdrawal, referral, subscritons)
//12. get notifications
//13. create notification & send it
//14. get banners
//15. create banners
//16. delete banner
//17. get categories
//18. create category
//19. delete category
//20. get all ads (all, active, completed)
//21. delete a ads
//22. delete photo of ads
//23. get app settings
//24. change app settings
//25. hide a ads


public function get_transaction_total(Request $request){
    $type=$request->txn_type;

    if($type=="ALL"){
        $transactions=Transaction::where('txn_status',"SUCCESS")
                                ->select(DB::raw('count(id) as total_count, coalesce(sum(txn_amount),0) as total_sum'))
                                ->get()->first();
    }else{
        $transactions=Transaction::where('txn_status',"SUCCESS")
                                ->where('txn_type',$type)
                                ->select(DB::raw('count(id) as total_count, coalesce(sum(txn_amount),0) as total_sum'))
                                ->get()->first();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transactions]);
}

//1. Login admin with username and password
public function login_admin(Request $request){
    $username=$request->username;
    $password=$request->password;


    $admin=Admin::where('username',$username)
                ->where('password',$password)
                ->first();

    if($admin){
        return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $admin]);
    }else{
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
    }
}



//2. get & search users sort by (name, balance, new first)
public function get_users(Request $request){
    $search=$request->search; //string 
    $from=$request->from; //string
    $first=$request->first; //0 or 1
    $type=$request->type; //NAME, BALANCE, NEW-FIRST
    $limit=$request->limit; //result count
    $fromId=$request->from_id;
    $filterType=$request->filter_type;

    $comp='>';
    $order='ASC';
    $orderBy='name';

    if($type=="NAME"){
        $comp='>';
        $order='ASC';
        $orderBy='name';
    }else if($type=="BALANCE"){
        $comp='<';
        $order='DESC';
        $orderBy='wallet_balance';
    }else if("NEW-FIRST"){
        $comp='<';
        $order='DESC';
        $orderBy='created_at';
    }

    $premiumType=array();
    if($filterType=="ALL"){
        array_push($premiumType,0);
        array_push($premiumType,1);
    }else if($filterType=="PREMIUM"){
        array_push($premiumType,1);
    }else{
        array_push($premiumType,0);
    }


    if($first==1){
        $users=User::where(function($q) use ($search){
                            $q->where('name','like','%'.$search.'%')
                                ->orWhere('email','like','%'.$search.'%')
                                ->orWhere('phone','like','%'.$search.'%');
                        })->whereIn('is_premium',$premiumType)
                        ->orderBy($orderBy,$order)
                        ->orderBy('id','DESC')
                        ->limit($limit)
                        ->get();
    }else{
        $users=User::where(function($q) use ($search){
                            $q->where('name','like','%'.$search.'%')
                                ->orWhere('email','like','%'.$search.'%')
                                ->orWhere('phone','like','%'.$search.'%');
                        })->whereIn('is_premium',$premiumType)
                        ->orderBy($orderBy,$order)
                        ->orderBy('id','DESC')
                        ->where(DB::raw('concat('.$orderBy.'," ",id)'),$comp,$from." ".$fromId)
                        ->limit($limit)
                        ->get();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $users]);
}

public function block_unblock_user(Request $request){
    $userId=$request->user_id;

    $user=User::where('id',$userId)
            ->first();

    if($user){
        if($user->is_blocked==0){
            $user->is_blocked=1;
        }else{
            $user->is_blocked=0;
        }
        $user->save();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $user]);
}

//3. get user transactions
public function get_user_transactions(Request $request){
    $fromId=$request->from_id;
    $userId=$request->user_id;
    $limit=$request->limit;

    if($fromId==0){
        $transactions=Transaction::where('user_id',$userId)
                                ->orderBy('created_at','DESC')
                                ->limit($limit)
                                ->get();
    }else{
        $transactions=Transaction::where('user_id',$userId)
                                ->where('id','<',$fromId)
                                ->orderBy('created_at','DESC')
                                ->limit($limit)
                                ->get();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transactions]);
}

//4. get user chats
public function get_user_chats(Request $request){
    $fromId=$request->from_id;
    $userId=$request->user_id;
    $limit=$request->limit;


    if($fromId==0){
        $chats=Chat::leftJoin('users',function($join){
                        $join->on('chats.seller_user_id','=','users.id')
                            ->orOn('chats.buyer_user_id','=','users.id');
                    })
                    ->where(function($q) use ($userId){
                        $q->where('chats.seller_user_id',$userId)
                            ->orWhere('chats.buyer_user_id',$userId);
                    })
                    ->select(DB::raw('users.id as user_id,users.name,users.profile,users.phone,chats.seller_user_id,chats.buyer_user_id,chats.ads_id,chats.blocked_id,chats.id as chat_id'))
                    ->where('users.id','<>',$userId)
                    ->orderBy('chats.created_at','DESC')
                    ->limit($limit)
                    ->get();
    }else{
         $chats=Chat::leftJoin('users',function($join){
                        $join->on('chats.seller_user_id','=','users.id')
                            ->orOn('chats.buyer_user_id','=','users.id');
                    })
                    ->select(DB::raw('users.id as user_id,users.name,users.profile,users.phone,chats.seller_user_id,chats.buyer_user_id,chats.ads_id,chats.blocked_id,chats.id as chat_id'))
                    ->where(function($q) use ($userId){
                        $q->where('chats.seller_user_id',$userId)
                            ->orWhere('chats.buyer_user_id',$userId);
                    })
                    ->where('users.id','<>',$userId)
                    ->orderBy('chats.created_at','DESC')
                    ->where('chats.id','<',$fromId)
                    ->limit($limit)
                    ->get();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $chats]);
}

//5. get user ads
public function get_user_ads(Request $request){
    $fromId=$request->from_id;
    $userId=$request->user_id;
    $limit=$request->limit;

    if($fromId==0){
        $ads=Advertisement::where('user_id',$userId)
                        ->orderBy('created_at','DESC')
                        ->limit($limit)
                        ->get();
    }else{
        $ads=Advertisement::where('user_id',$userId)
                        ->where('id','<',$fromId)
                        ->orderBy('created_at','DESC')
                        ->limit($limit)
                        ->get();
    }
    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $ads]);
}


//6. get chat messages
public function get_chat_messages(Request $request){
    $fromId=$request->from_id;
    $limit=$request->limit;
    $chatId=$request->chat_id;

    if($fromId==0){
        $messages=Message::where('chat_id',$chatId)
                        ->orderBy('created_at','DESC')
                        ->limit($limit)
                        ->get();
    }else{
        $messages=Message::where('chat_id',$chatId)
                        ->where('id','<',$fromId)
                        ->orderBy('created_at','DESC')
                        ->limit($limit)
                        ->get();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $messages]);
}

//7. get ads details
public function get_user_ads_detail($adsId){
    $adsDetail=Advertisement::where('id',$adsId)
                            ->with('advertisementImage')
                            ->first();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $adsDetail]);
}


//8. get subscriptions (NEW-FIRST, EXPIRING-SOON)
public function get_user_subscriptions(Request $request){
    $from=$request->from;
    $fromId=$request->from_id;
    $first=$request->first;
    $limit=$request->limit;
    $type=$request->type;
    $comp='<';
    $order='DESC';

    if($type=="NEW-FIRST"){
        $comp='<';
        $order='DESC';
    }else{
        $comp='>';
        $order='ASC';
    }

    if($first==1){
        $subscriptions=User::where('is_premium',1)
                            ->orderBy('subscription_ends',$order)
                            ->orderBy('id','DESC')
                            ->limit($limit)
                            ->get();

    }else{
        $subscriptions=User::where('is_premium',1)
                            ->where(DB::raw('CONCAT(subscription_ends," ",id)'),$comp,$from." ".$fromId)
                            ->orderBy('subscription_ends',$order)
                            ->orderBy('id','DESC')
                            ->limit($limit)
                            ->get();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $subscriptions]);
}

//9. cancel subscriptions
public function cancel_subscription(Request $request){
    $userId=$request->user_id;
    // $message=$request->message;

    $subscription=User::where('id',$userId)
                    ->first();

    if($subscription&&$subscription->is_premium==1){
        $subscription->is_premium=0;
        $subscription->save();

        //send notification to user $message
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $subscription]);
}

//10. extend subscriptions
public function extend_subscription(Request $request){
    $userId=$request->user_id;
    $expiryDate=$request->expiry_date;

    $subscription=User::where('id',$userId)
                    ->first();

    if($subscription){
        if($subscription->is_premium==0){
            $subscription->is_premium=1;
        }
        $subscription->subscription_ends=$expiryDate;
        $subscription->save();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $subscription]);
}

//11. get overall transactions (withdrawal, referral, subscritons)
public function get_overall_transactions(Request $request){
    $fromId=$request->from_id;
    $limit=$request->limit;
    $type=$request->type;

    if($fromId==0){
        if($type=="ALL"){
            $transactions=Transaction::orderBy('created_at','DESC')
                                    ->limit($limit)
                                    ->get();
        }else{
            $transactions=Transaction::where('txn_type',$type)
                                    ->orderBy('created_at','DESC')
                                    ->limit($limit)
                                    ->get();
        }

    }else{
        if($type=="ALL"){
            $transactions=Transaction::where('id','<',$fromId)
                                    ->orderBy('created_at','DESC')
                                    ->limit($limit)
                                    ->get();
        }else{
            $transactions=Transaction::where('txn_type',$type)
                                    ->where('id','<',$fromId)
                                    ->orderBy('created_at','DESC')
                                    ->limit($limit)
                                    ->get();
        }
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transactions]);
}


//12. get notifications
public function get_notifications(Request $request){
    $fromId=$request->from_id;
    $limit=$request->limit;

    if($fromId==0){
        $notifications=Notification::orderBy('created_at','DESC')
                                    ->limit($limit)
                                    ->get();
    }else{
        $notifications=Notification::where('id','<',$fromId)
                                    ->orderBy('created_at','DESC')
                                    ->limit($limit)
                                    ->get();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $notifications]);
}

//13. create notification & send it
public function create_notification(Request $request){
    $title=$request->notification_title;
    $message=$request->notification_message;
    $sendTo=$request->send_to;

    $tokenList=[];

    if($sendTo=="ALL"){
        $users=User::pluck('token');
    }else if($sendTo=="SUBSCRIBERS"){
        $users=User::where('is_premium',1)
                    ->pluck('token');
    }else{
        $users=User::where('is_premium',0)
                    ->pluck('token');
    }

    foreach($users as $token){
        array_push($tokenList,$token);
    }

    //send notification to token list

    $notificationData=[];
    $notificationData['notification_title']=$title;
    $notificationData['notification_message']=$message;
    $notificationData['send_to']=$sendTo;

    $notification=Notification::create($notificationData);

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $notification]);
}


//14. get banners
public function get_banners(){
    $banners=Banner::get();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $banners]);
}


//15. create banners
public function create_banner(Request $request){
    $linkType=$request->link_type;
    $bannerLink=$request->banner_link;

    $bannerCount=Banner::count();

    if($bannerCount>=20){
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
    }

    $bannerData=[];
    $bannerData['link_type']=$linkType;
    $bannerData['banner_link']=$bannerLink;

    if ($request->hasFile('banner_image')) {
        $image = $request->file('banner_image');
        $name = time() . '.' . $image->getClientOriginalExtension();
        $destinationPath = public_path('/Banners');
        $image->move($destinationPath, $name);

        $bannerData['banner_image']=$name;
    }

    $banner=Banner::create($bannerData);

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $banner]);
}

public function update_banner(Request $request){
    $bannerId=$request->banner_id;
    $linkType=$request->link_type;
    $bannerLink=$request->banner_link;

    $banner=Banner::where('id',$bannerId)
                ->first();

    if(!$banner){
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
    }

    $banner->link_type=$linkType;
    $banner->banner_link=$bannerLink;
    
    if ($request->hasFile('banner_image')) {
        $image = $request->file('banner_image');
        $name = time() . '.' . $image->getClientOriginalExtension();
        $destinationPath = public_path('/Banners');
        $image->move($destinationPath, $name);

        $banner->banner_image=$name;
    }

    $banner->save();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $banner]);
}

//16. delete banner
public function delete_banner($bannerId){
    $banner=Banner::where('id',$bannerId)
                ->delete();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);        
}

//17. get categories
public function get_categories(){
    $categories=Category::get();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $categories]);        
}


//18. create category
public function create_category(Request $request){
    $categoryName=$request->category_name;
    $active=$request->is_active;

    $categoryData=[];
    $categoryData['category_name']=$categoryName;
    $categoryData['is_active']=$active;

    if ($request->hasFile('category_image')) {
        $image = $request->file('category_image');
        $name = time() . '.' . $image->getClientOriginalExtension();
        $destinationPath = public_path('/Categories');
        $image->move($destinationPath, $name);

        $categoryData['category_image']=$name;
    }

    $category=Category::create($categoryData);

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $category]);        
}


public function update_category(Request $request){
    $catId=$request->category_id;
    $categoryName=$request->category_name;
    $active=$request->is_active;

    $category=Category::where('id',$catId)
                    ->first();

    if(!$category){
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);        
    }

    $category->category_name=$categoryName;
    $category->is_active=$active;

    if ($request->hasFile('category_image')) {
        $image = $request->file('category_image');
        $name = time() . '.' . $image->getClientOriginalExtension();
        $destinationPath = public_path('/Categories');
        $image->move($destinationPath, $name);

        $category->category_image=$name;
    }

    $category->save();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $category]);        
}

//19. delete category
public function delete_category($catId){
    $category=Category::where('id',$catId)
                    ->delete();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);           
}


//20. get all ads (all, active, completed)
public function get_all_ads(Request $request){
    $fromId=$request->from_id;
    $limit=$request->limit;
    $status=$request->status;

    if($fromId==0){
        if($status=="ALL"){
            $ads=Advertisement::orderBy('created_at','DESC')
                            ->limit($limit)
                            ->get();
        }else{
            $ads=Advertisement::where('status',$status)
                            ->orderBy('created_at','DESC')
                            ->limit($limit)
                            ->get();
        }
    }else{
        if($status=="ALL"){
            $ads=Advertisement::where('id','<',$fromId)
                            ->orderBy('created_at','DESC')
                            ->limit($limit)
                            ->get();
        }else{
            $ads=Advertisement::where('id','<',$fromId)
                            ->where('status',$status)
                            ->orderBy('created_at','DESC')
                            ->limit($limit)
                            ->get();
        }
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $ads]);
}


//21. delete a ads
public function delete_ads($adsId){
    $ads=Advertisement::where('id',$adsId)
                    ->first();

    if($ads){
        $ads->is_deleted=1;
        $ads->save();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $ads]);
}


//22. delete photo of ads
public function delete_ads_photo(Request $request){
    $adsId=$request->ads_id;
    $imageId=$request->image_id;

    $ads=Advertisement::where('id',$adsId)
                    ->first();

    $adsImage=AdvertisementImage::where('id',$imageId)
                                ->first();

    $imageCount=AdvertisementImage::where('ads_id',$adsId)
                                ->orderBy('id','ASC')
                                ->get();

    if($ads&&$adsImage){
        if($ads->primary_image==$adsImage->image_link){
            if(count($imageCount)==1){
                $ads->primary_image=null;

            }else{
                $newPrimary=$imageCount[1]['image_link'];
                $ads->primary_image=$newPrimary;
            }

            $ads->save();
        }

        $path = url('') . '/Advertisements/';

        File::delete($path.$adsImage['image_link']);
        $img=AdvertisementImage::where('id',$imageId)
                            ->delete();

        return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
    }

    return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
}


//23. get app settings
public function get_app_settings(){
    $settings=Setting::first();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $settings]);
}

//24. change app settings
public function change_app_settings(Request $request){
    $setting=Setting::first();

    $setting->cfp_auth_id=$request->cfp_auth_id;
    $setting->cfp_secret_key=$request->cfp_secret_key;
    $setting->cf_app_id=$request->cf_app_id;
    $setting->cf_secret_key=$request->cf_secret_key;
    $setting->free_ads_count=$request->free_ads_count;
    $setting->free_withdrawal_count=$request->free_withdrawal_count;
    $setting->monthly_subs_price=$request->monthly_subs_price;
    $setting->yearly_subs_price=$request->yearly_subs_price;
    $setting->from_refer_amount=$request->from_refer_amount;
    $setting->to_refer_amount=$request->to_refer_amount;
    $setting->sms_api_key=$request->sms_api_key;
    $setting->sms_senderid=$request->sms_senderid;

    $setting->save();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $setting]);
}


//25. hide a ads
public function hide_ads($adsId){
    $ads=Advertisement::where('id',$adsId)
                    ->first();

    if($ads){
        $ads->is_hidden=1;
        $ads->save();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $ads]);
}


}
