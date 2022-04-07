<?php

namespace App\Http\Controllers;

use App\Mail\UserMail;
use App\Models\Advertisement;
use App\Models\AdvertisementImage;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Chat;
use App\Models\MailVerification;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBank;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
//1. Get metadata (Settings etc)
//2. Login user with phone and otp
//3. Login user with email and password
//4. Forgot password web version
//5. Verify email using link sent on email
//6. Verify phone and create account
//7. Get homepage data (banners, categories)
//8. sellers nearby based on the location of user
//9. get users chats (all, buyer, seller)
//10. get my ads (all, active, completed)
//11. get user details
//12. get create ads data (categories)
//13. create a new ads
//14. get ads details
//15. delete a ads
//16. mark an ads completed
//17. delete an ads
//18. edit profile and save
//19. get wallet and transactions
//20. withdraw amount to bank (razorpay)
//21. get user bank details
//22. get user subscriptions
//23. buy subscriptions (razorpay)
//24. get refer details and create share link
//25. get informations
//26. get chat messages & user details
//27. send message to user
//28. upload image and send message data
//29. block a user //optional
//30. Get All categories



//1. Get metadata (Settings etc)
public function get_metadata($userId){

    $details = [
        'title' => 'Mail from Aryan',
        'body' => 'Hello HRMC'
    ];
   
    Mail::to('hrithik24365@gmail.com')->send(new UserMail($details));
   
}


//2. Login user with phone and otp
public function login_phone(Request $request){
    $phone=$request->phone;
    $type=$request->type;

    $user=User::where('phone',$phone)
                ->first();

    if($user){
        if($user->is_blocked==1){
            //user account blocked
            return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
        }
    }else{
        //user doesn't exist
        return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
    }

    if($type=="SEND-OTP"){
        $code=$this->getOtp(6);
        $message="Your verification code for login is ".$code;

        // $result=$this->sendOtp($message,$phone);

        // if($result&&$result['status']=="OK"){
            //otp sent
            $user->otp=$code;
            $user->save();

            return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);

        // }else{
        //     //Failed to send otp
        //     return response()->json(['status' => 'FAILED', 'code' => 'FC_03', 'data' => null]);
        // }

    }else{
        $token=$request->token;
        $otp=$request->otp;

        $user=User::where('phone',$phone)
                ->where('otp',$otp)
                ->first();

        if($user){
            //login successful
            $user->token=$token;
            $user->save();
            return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $user]);
        }else{
            //wrong credentials
            return response()->json(['status' => 'FAILED', 'code' => 'FC_04', 'data' => null]);
        }
    }
}


function getOtp($n)
	{
		$characters = '0123456789';
		$randomString = '';

		for ($i = 0; $i < $n; $i++) {
			$index = rand(0, strlen($characters) - 1);
			$randomString .= $characters[$index];
		}

		return $randomString;
}


public function sendOtp($message,$number){
    $otpData=[];
    $otpData['number']=$number;
    

    $setting=Setting::first();

    $otpData['apiKey']=$setting->sms_api_key;
    $otpData['senderid']=$setting->sms_senderid;
    $otpData['message']=$message;
    $otpData['format']="json";

    $data_string = json_encode($otpData);

        $ch = curl_init("http://13.234.91.92/V2/http-api-post.php");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)			
                )
        );

        $result =curl_exec($ch);
        curl_close($ch);

        return $result;
}


//3. Login user with email and password
public function login_email(Request $request){
    $email=$request->email;
    $password=$request->password;
    $token=$request->token;

    $user=User::where('email',$email)
                ->where('password',$password)
                ->first();
    
    if($user){
        if($user->is_blocked==1){
            //user account blocked
            return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
        }

        $user->token=$token;
        $user->save();

        return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $user]);
    }else{
        //user account doesn't exist
        return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);
    }
}


//4. Forgot password web version
public function forgot_password(Request $request){
    //send link to mail to reset password
    //link should work once

    $uid=$request->uid;
    $password=$request->password;

    $mailVerification=MailVerification::where('uid',$uid)
                                    ->first();

    if(!$mailVerification){
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
    }

    $userId=$mailVerification->user_id;

    MailVerification::where('uid',$uid)
                    ->delete();

    $user=User::where('id',$userId)
            ->first();
        
    $user->password=$password;
    $user->save();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
}

public function send_forgot_password_mail(Request $request){
    $email=$request->email;
    $userId=$request->user_id;

    $mailVerificationData=[];
    $mailVerificationData['email']=$email;
    $mailVerificationData['user_id']=$userId;
    $mailVerificationData['uid']=$this->getCode(12);

    $mailVerifications=MailVerification::where('user_id',$userId)
                                        ->delete();

    $mailVerification=MailVerification::create($mailVerificationData);

    $details = [
        'title' => 'Mail from Aryan',
        'body' => 'Hello HRMC'
    ];
   
    Mail::to('hrithik24365@gmail.com')->send(new UserMail($details));

    return $mailVerification;
}

public function send_verification_mail(Request $request){
    $email=$request->email;
    $userId=$request->user_id;

    $mailVerificationData=[];
    $mailVerificationData['email']=$email;
    $mailVerificationData['user_id']=$userId;
    $mailVerificationData['uid']=$this->getCode(12);

    $mailVerifications=MailVerification::where('user_id',$userId)
                                        ->delete();

    $mailVerification=MailVerification::create($mailVerificationData);

    $details = [
        'title' => 'Mail from Aryan',
        'body' => 'Hello HRMC'
    ];
   
    Mail::to('hrithik24365@gmail.com')->send(new UserMail($details));

    return $mailVerification;
}

//5. Verify email using link sent on email
public function verify_email($uid){
    //send verification link to email and mark email verified...

    $mailVerification=MailVerification::where('uid',$uid)
                                    ->first();

    if(!$mailVerification){
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
    }

    $userId=$mailVerification->user_id;

    MailVerification::where('uid',$uid)
                    ->delete();

    $user=User::where('id',$userId)
            ->update(['is_email_verified'=>1]);

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
}


public function reset_password(Request $request){
    $userId=$request->user_id;
    $currentPassword=$request->current_password;
    $newPassword=$request->new_password;

    $user=User::where('id',$userId)
                ->where('password',$currentPassword)
                ->first();
            
    if(!$user){
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
    }

    $user->password=$newPassword;
    $user->save();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
}

//6. Verify phone and create account
public function send_phone_otp(Request $request){
    $phone=$request->phone;

    $user=User::where('phone',$phone)
                ->first();

    if($user){
        //user already exist
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
    }else{
        // create basic user

        $userData=[];
        $userData['name']="";
        $userData['email']=$phone;
        $userData['phone']=$phone;

        $code=$this->getOtp(6);
        // $message="Your verification code is ".$code;

        // $result=$this->sendOtp($message,$phone);

        // if($result&&$result['status']=="OK"){
            //otp sent
            $userData['otp']=$code;
            $user=User::create($userData);

            return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
        // }else{
        //     //Failed to send otp
        //     return response()->json(['status' => 'FAILED', 'code' => 'FC_03', 'data' => null]);
        // }

    }
}

public function verify_phone_otp(Request $request){
    $phone=$request->phone;
    $otp=$request->otp;

    $user=User::where('phone',$phone)
                ->where('otp',$otp)
                ->first();

    if($user){
        return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);
    }

    return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
}

public function create_user_account(Request $request){
    $name=$request->name;
    $email=$request->email;
    $password=$request->password;
    $phone=$request->phone;

    $user=User::where('phone',$phone)
                ->first();

    if($user&&$user->email==$user->phone){
        $user->name=$name;
        $user->email=$email;
        $user->password=$password;

        $user->save();
        return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $user]);
    }else{
        //user account already exist
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);
    }
}



function getCode($n)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';

		for ($i = 0; $i < $n; $i++) {
			$index = rand(0, strlen($characters) - 1);
			$randomString .= $characters[$index];
		}

		return $randomString;
	}



//7. Get homepage data (banners, categories)
public function get_homepage(Request $request){
    $lat=$request->lat;
    $lng=$request->lng;
    $radius=$request->radius;
    $limit=$request->limit;

    $banners=Banner::get();

    $categories=Category::where('is_active',1)
                    ->orderBy('created_at','ASC')
                    ->limit(8)
                    ->get();

    $result=[];

    if($request->has('radius')){
        $sql="SELECT id,category_id,ads_title,primary_image,address,price,
		round(( 6371 * 
			ACOS( 
				COS( RADIANS( lat ) ) * 
				COS( RADIANS( $lat ) ) * 
				COS( RADIANS( $lng ) - 
				RADIANS( lng ) ) + 
				SIN( RADIANS( lat ) ) * 
				SIN( RADIANS( $lat) ) 
			) 
		),2)
		AS distance FROM advertisements WHERE is_hidden=0 AND is_deleted=0  HAVING distance <= $radius ORDER BY distance ASC, id DESC LIMIT $limit";
		
		$advertisements=DB::select($sql);
    $result['advertisements']=$advertisements;
    }
   

    $result['banners']=$banners;
    $result['categories']=$categories;

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $result]);
}

public function get_more_ads(Request $request){
    $fromDistance=$request->from_distance;
    $fromId=$request->from_id;
    $limit=$request->limit;
    $lat=$request->lat;
    $lng=$request->lng;
    $radius=$request->radius;


    if($fromId==0){
        $sql="SELECT id,category_id,ads_title,primary_image,address,price,
		round(( 6371 * 
			ACOS( 
				COS( RADIANS( lat ) ) * 
				COS( RADIANS( $lat ) ) * 
				COS( RADIANS( $lng ) - 
				RADIANS( lng ) ) + 
				SIN( RADIANS( lat ) ) * 
				SIN( RADIANS( $lat) ) 
			) 
		),2)
		AS distance FROM advertisements WHERE is_hidden=0 AND is_deleted=0  HAVING distance <= $radius ORDER BY distance ASC, id DESC LIMIT $limit";
    }else{
        $sql="SELECT id,category_id,ads_title,primary_image,address,price,
		round(( 6371 * 
			ACOS( 
				COS( RADIANS( lat ) ) * 
				COS( RADIANS( $lat ) ) * 
				COS( RADIANS( $lng ) - 
				RADIANS( lng ) ) + 
				SIN( RADIANS( lat ) ) * 
				SIN( RADIANS( $lat) ) 
			) 
		),2)
		AS distance FROM advertisements WHERE is_hidden=0 AND is_deleted=0 AND CONCAT(distance,' ',id)>=CONCAT($fromDistance,' ',$fromId) HAVING distance <= $radius ORDER BY distance ASC, id DESC LIMIT $limit";
    }

    $advertisements=DB::select($sql);

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $advertisements]);
}


public function get_category_ads(Request $request){
    $fromDistance=$request->from_distance;
    $fromId=$request->from_id;
    $limit=$request->limit;
    $lat=$request->lat;
    $lng=$request->lng;
    $radius=$request->radius;
    $categoryId=$request->category_id;

    if($fromId==0){
        $sql="SELECT id,category_id,ads_title,primary_image,address,price,
		round(( 6371 * 
			ACOS( 
				COS( RADIANS( lat ) ) * 
				COS( RADIANS( $lat ) ) * 
				COS( RADIANS( $lng ) - 
				RADIANS( lng ) ) + 
				SIN( RADIANS( lat ) ) * 
				SIN( RADIANS( $lat) ) 
			) 
		),2)
		AS distance FROM advertisements WHERE is_hidden=0 AND is_deleted=0 AND category_id=$categoryId  HAVING distance <= $radius ORDER BY distance ASC, id DESC LIMIT $limit";
    }else{
        $sql="SELECT id,category_id,ads_title,primary_image,address,price,
		round(( 6371 * 
			ACOS( 
				COS( RADIANS( lat ) ) * 
				COS( RADIANS( $lat ) ) * 
				COS( RADIANS( $lng ) - 
				RADIANS( lng ) ) + 
				SIN( RADIANS( lat ) ) * 
				SIN( RADIANS( $lat) ) 
			) 
		),2)
		AS distance FROM advertisements WHERE is_hidden=0 AND is_deleted=0 AND category_id=$categoryId AND CONCAT(distance,' ',id)>=CONCAT($fromDistance,' ',$fromId) HAVING distance <= $radius ORDER BY distance ASC, id DESC LIMIT $limit";
    }

    $advertisements=DB::select($sql);

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $advertisements]);
}


//DONE //8. sellers nearby based on the location of user

//9. get users chats (all, buyer, seller)
public function get_my_chats(Request $request){
    $userId=$request->user_id;
    $type=$request->type;
    $fromId=$request->from_id;
    $limit=$request->limit;

    if($fromId==0){
        if($type=="ALL"){
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
        }else if($type=="SELLER"){
            $chats=Chat::leftJoin('users',function($join){
                        $join->on('chats.buyer_user_id','=','users.id')
                            ->orOn('chats.seller_user_id','=','users.id');
                    })
                    ->select(DB::raw('users.id as user_id,users.name,users.profile,users.phone,chats.seller_user_id,chats.buyer_user_id,chats.ads_id,chats.blocked_id,chats.id as chat_id'))
                    ->where('chats.buyer_user_id',$userId)
                    ->where('users.id','<>',$userId)
                    ->orderBy('chats.created_at','DESC')
                    ->limit($limit)
                    ->get();
        }else {
            $chats=Chat::leftJoin('users',function($join){
                        $join->on('chats.seller_user_id','=','users.id')
                            ->orOn('chats.buyer_user_id','=','users.id');
                    })
                    ->select(DB::raw('users.id as user_id,users.name,users.profile,users.phone,chats.seller_user_id,chats.buyer_user_id,chats.ads_id,chats.blocked_id,chats.id as chat_id'))
                    ->where('chats.seller_user_id',$userId)
                    ->where('users.id','<>',$userId)
                    ->orderBy('chats.created_at','DESC')
                    ->limit($limit)
                    ->get();
        }
        
    }else{

        if($type=="ALL"){
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
                    ->where('chats.id','<',$fromId)
                    ->orderBy('chats.created_at','DESC')
                    ->limit($limit)
                    ->get();
        }else if($type=="SELLER"){
            $chats=Chat::leftJoin('users',function($join){
                        $join->on('chats.buyer_user_id','=','users.id')
                            ->orOn('chats.seller_user_id','=','users.id');
                    })
                    ->select(DB::raw('users.id as user_id,users.name,users.profile,users.phone,chats.seller_user_id,chats.buyer_user_id,chats.ads_id,chats.blocked_id,chats.id as chat_id'))
                    ->where('chats.buyer_user_id',$userId)
                    ->where('users.id','<>',$userId)
                    ->where('chats.id','<',$fromId)
                    ->orderBy('chats.created_at','DESC')
                    ->limit($limit)
                    ->get();
        }else {
            $chats=Chat::leftJoin('users',function($join){
                        $join->on('chats.seller_user_id','=','users.id')
                            ->orOn('chats.buyer_user_id','=','users.id');
                    })
                    ->select(DB::raw('users.id as user_id,users.name,users.profile,users.phone,chats.seller_user_id,chats.buyer_user_id,chats.ads_id,chats.blocked_id,chats.id as chat_id'))
                    ->where('chats.seller_user_id',$userId)
                    ->where('users.id','<>',$userId)
                    ->where('chats.id','<',$fromId)
                    ->orderBy('chats.created_at','DESC')
                    ->limit($limit)
                    ->get();
        }
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $chats]);
}


//10. get my ads (all, active, completed)
public function get_my_ads(Request $request){
    $userId=$request->user_id;
    $fromId=$request->from_id;
    $type=$request->type;
    $limit=$request->limit;

    if($fromId==0){
        if($type=="ALL"){
            $ads=Advertisement::where('user_id',$userId)
                            ->orderBy('created_at','DESC')
                            ->limit($limit)
                            ->get();
        }else{
            $ads=Advertisement::where('user_id',$userId)
                            ->where('status',$type)
                            ->orderBy('created_at','DESC')
                            ->limit($limit)
                            ->get();
        }
    }else{
        if($type=="ALL"){
            $ads=Advertisement::where('user_id',$userId)
                            ->where('id','<',$fromId)
                            ->orderBy('created_at','DESC')
                            ->limit($limit)
                            ->get();
        }else{
            $ads=Advertisement::where('user_id',$userId)
                            ->where('id','<',$fromId)
                            ->where('status',$type)
                            ->orderBy('created_at','DESC')
                            ->limit($limit)
                            ->get();
        }
    }


    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $ads]);                           
}


//11. get user details
public function get_user_details($userId){
    $user=User::where('id',$userId)
                ->first();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $user]);                               
}


//12. get create ads data (categories)
public function get_createads_data(){
    $categories=Category::where('is_active',1)
                        ->select('id','category_name')
                        ->get();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $categories]);                                  
}


//13. create a new ads
public function create_ads(Request $request){
    $userId=$request->user_id;
    $categoryId=$request->category_id;
    $adsTitle=$request->ads_title;
    $adsDescription=$request->ads_description;
    $lat=$request->lat;
    $lng=$request->lng;
    $address=$request->address;
    $price=$request->price;
    $chatAvailable=$request->is_chat_available;
    $callAvailable=$request->is_call_available;

    $user=User::where('id',$userId)
            ->first();

    $setting=Setting::first();

    $advertisementCount=Advertisement::where('user_id',$userId)
                                    ->count();
    
    if($advertisementCount==$setting->free_ads_count&&$user->is_premium==0){
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);                                  
    }

    DB::beginTransaction();

    try{

    $advertisementData=[];
    $advertisementData['user_id']=$userId;
    $advertisementData['category_id']=$categoryId;
    $advertisementData['ads_title']=$adsTitle;
    $advertisementData['ads_description']=$adsDescription;
    $advertisementData['lat']=$lat;
    $advertisementData['lng']=$lng;
    $advertisementData['address']=$address;
    $advertisementData['price']=$price;
    $advertisementData['is_chat_available']=$chatAvailable;
    $advertisementData['is_call_available']=$callAvailable;

    if ($request->hasFile('primary_image')) {
        $image = $request->file('primary_image');
        $name = time() . '.' . $image->getClientOriginalExtension();
        $destinationPath = public_path('/Advertisements');
        $image->move($destinationPath, $name);

        $advertisementData['primary_image']=$name;
    }

    $advertisement=Advertisement::create($advertisementData);

    $adsImageData=[];
    $adsImageData['ads_id']=$advertisement['id'];
    $adsImageData['image_link']=$advertisement['primary_image'];

    $adsImage=AdvertisementImage::create($adsImageData);

    DB::commit();

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $advertisement]);  

    }catch(Exception $e){
        DB::rollBack();
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);                                  
    }
}


public function upload_ads_images(Request $request){
    $adsId=$request->ads_id;
    
    $adsImageData=[];
    $adsImageData['ads_id']=$adsId;

    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $name = time() . '.' . $image->getClientOriginalExtension();
        $destinationPath = public_path('/Advertisements');
        $image->move($destinationPath, $name);

        $adsImageData['image_link']=$name;
    }

    $adsImage=AdvertisementImage::create($adsImageData);

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $adsImage]);  
}


//14. get ads details
public function get_ads_detail($adsId){
    $advertisement=Advertisement::where('id',$adsId)
                            ->first();

    $advertisementImages=AdvertisementImage::where('ads_id',$adsId)
                                        ->get();

    $advertisement['images']=$advertisementImages;

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $advertisement]);  
}


//15. delete a ads
public function delete_ads($adsId){
    $advertisement=Advertisement::where('id',$adsId)
                            ->first();

    if($advertisement){
        $advertisement->is_deleted=1;
        $advertisement->save();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);  
}


//16. mark an ads completed
public function mark_ads_completed($adsId){
    $advertisement=Advertisement::where('id',$adsId)
                            ->first();

    if($advertisement){
        $advertisement->is_hidden=2;
        $advertisement->save();
    }

    return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => null]);  
}


//DONE //17. delete an ads

//18. edit profile and save
public function update_profile(Request $request){
    $userId=$request->user_id;
    $name=$request->name;
    $email=$request->email;
    $phone=$request->phone;

    $user=User::where('id',$userId)
            ->first();
    
    if($user){
        $user->name=$name;
        $user->email=$email;
        $user->phone=$phone;

        if ($request->hasFile('profile')) {
            $image = $request->file('profile');
            $fname = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('/Profiles');
            $image->move($destinationPath, $fname);
    
            $user->profile=$fname;
        }

        $user->save();

        return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $user]);  
    }

    return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);  
}

//19. get wallet and transactions
public function get_transactions(Request $request){
    $userId=$request->user_id;
    $fromId=$request->from_id;
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


//20. withdraw amount to bank (cashfree)
public function withdraw_to_bank(Request $request){
    $userId=$request->user_id;
    $transferId=$request->transfer_id;
    $accountName=$request->account_name;
    $accountNumber=$request->account_number;
    $ifscCode=$request->ifsc_code;
    $amount=$request->amount;

    $cfpBeneData=[];
    $cfpBeneData['user_id']=$userId;
    $cfpBeneData['account_name']=$accountName;
    $cfpBeneData['account_number']=$accountNumber;
    $cfpBeneData['ifsc_code']=$ifscCode;

    $setting=Setting::first();
    $user=User::where('id',$userId)
                ->first();

    $withdrawalCount=Transaction::where('user_id',$userId)
                            ->where('txn_type','WITHDRAW')
                            ->where('txn_status','SUCCESS')
                            ->count();

    if($withdrawalCount==$setting->free_withdrawal_count&&$user->is_premium==0){
        //premium required to withdraw amount
        return response()->json(['status' => 'FAILED', 'code' => 'FC_01', 'data' => null]);  
    }

    if($user->wallet_balance<$amount){
        //low wallet balance
        return response()->json(['status' => 'FAILED', 'code' => 'FC_02', 'data' => null]);  
    }

    DB::beginTransaction();

    try{

    $beneficiary=$this->add_cfp_beneficiary($cfpBeneData);

    if($beneficiary){
        $authKeyData=$beneficiary['auth'];
        $bankDetails=$beneficiary['bank'];

        $transactionData=[];
        $transactionData['user_id']=$userId;
        $transactionData['txn_title']="Withdraw from wallet";
        $transactionData['txn_mode']="WALLET";
        $transactionData['txn_type']="WITHDRAW";
        $transactionData['txn_status']="PENDING";
        $transactionData['txn_amount']=$amount;

        $walletBalance=$user->wallet_balance;
        $updatedWalletBalance=$walletBalance-$amount;

        $user->wallet_balance=$updatedWalletBalance;
        $user->save();

        $transactionData['closing_balance']=$updatedWalletBalance;

        $transaction=Transaction::create($transactionData);

        $withdrawalData=[];
        $withdrawalData['user_id']=$userId;
        $withdrawalData['txn_id']=$transaction['id'];
        $withdrawalData['txn_amount']=$amount;
        $withdrawalData['account_number']=$accountNumber;
        $withdrawalData['account_name']=$accountName;
        $withdrawalData['ifsc_code']=$ifscCode;

        $withdrawal=Withdrawal::create($withdrawalData);

        $transferData=[];
        $transferData['beneId']=$userId;
        $transferData['amount']=$amount;
        $transferData['transferId']=$transferId;


        $authKey=$authKeyData['auth_key'];

		$post_data = json_encode($transferData, JSON_UNESCAPED_SLASHES);

		$url = "https://payout-api.cashfree.com/payout/v1.2/requestTransfer";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$headers=['Content-Type: application/json',
					'Authorization: Bearer '.$authKey];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		$response = curl_exec($ch);

		$responseData=json_decode($response,JSON_UNESCAPED_SLASHES);

        if($responseData['subCode']==200&&$responseData['status']=="SUCCESS"){
            $transaction->txn_status="SUCCESS";
            $transaction->reference_id=$responseData['data']['referenceId'];
            $withdrawal->utr=$responseData['data']['utr'];
            $transaction->save();
            $withdrawal->save();
        }else if(($responseData['subCode']==201&&$responseData['status']=="SUCCESS")||$responseData['status']=="PENDING"){
            $transaction->reference_id=$responseData['data']['referenceId'];
            $withdrawal->utr=$responseData['data']['utr'];
            $transaction->save();
            $withdrawal->save();
        }else{
            $transaction->txn_status="FAILED";
            $transaction->closing_balance=$transaction->closing_balance+$amount;
            $user->wallet_balance=$user->wallet_balance+$amount;
            $transaction->save();
            $user->save();
        }

        DB::commit();
        return response()->json(['status' => 'SUCCESS', 'code' => 'SC_01', 'data' => $transaction]);  
    }

    DB::rollBack();
    //beneficiary not created
    return response()->json(['status' => 'FAILED', 'code' => 'FC_03', 'data' => null]);  

    }catch(Exception $e){
        DB::rollBack();
        //something went wrong
        return response()->json(['status' => 'FAILED', 'code' => 'FC_04', 'data' => null]);  
    }
}

public function get_cfp_auth_key(){
    $setting=Setting::first();
    $appId=$setting->cfp_app_id;
    $secretKey=$setting->cfp_secret_key;
    $authExpiry=$setting->cfp_auth_expiry;
    $auth=$setting->cfp_auth;

    $urlVerify="https://payout-api.cashfree.com/payout/v1/verifyToken";

    $chVerify = curl_init($urlVerify);
    curl_setopt($chVerify, CURLOPT_POST, 1);
    curl_setopt($chVerify, CURLOPT_RETURNTRANSFER, true); 
    $headersVerify=['Content-Type: application/json',
                'Authorization: Bearer '.$auth];
    curl_setopt($chVerify, CURLOPT_HTTPHEADER, $headersVerify); 
    $responseVerify = curl_exec($chVerify);

    $responseDataVerify=json_decode($responseVerify,JSON_UNESCAPED_SLASHES);

    if($responseDataVerify['subCode']==200&&$responseDataVerify['status']=="SUCCESS"){
        $resultData=[];
        $resultData['auth_key']=$setting->cfp_auth;
        return $resultData;
    }

    $url = "https://payout-api.cashfree.com/payout/v1/authorize";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $headers=['Content-Type: application/json',
                'x-client-id: '.$appId,
                'x-client-secret: '.$secretKey];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    $response = curl_exec($ch);

    $responseData=json_decode($response,JSON_UNESCAPED_SLASHES);


    if($responseData['subCode']==200&&$responseData['status']=="SUCCESS"){
        //Save the token to database and expiry time
        $setting->cfp_auth=$responseData['data']['token'];
        $setting->cfp_auth_expiry=$responseData['data']['expiry'];
        $setting->save();
    }
    

    $resultData=[];
    $resultData['auth_key']=$setting->cfp_auth;

    return $resultData;
}

public function add_cfp_beneficiary($request){
    $userId=$request['user_id'];
    $accountNumber=$request['account_number'];
    $accountName=$request['account_name'];
    $ifscCode=$request['ifsc_code'];

    $beneficiaryData=[];
    $beneficiaryData['beneId']=$userId;
    $beneficiaryData['bankAccount']=$accountNumber;
    $beneficiaryData['name']=$accountName;
    $beneficiaryData['ifsc']=$ifscCode;


    $user=User::where('id',$userId)->first();

    if($user->email==null||$user->address==null||$user->city==null||$user->state==null||$user->pin_code==null){
        return response()->json(['status'=>'FAILURE','code'=>'FC_02','data'=>null]);
    }

    $beneficiaryData['email']=$user->email;
    $beneficiaryData['phone']=$user->phone;
    $beneficiaryData['address1']=$user->address;
    $beneficiaryData['city']=$user->city;
    $beneficiaryData['state']=$user->state;
    $beneficiaryData['pincode']=$user->pin_code;


    $userBank=UserBank::where('user_id',$userId)->first();

    $authKeyData=$this->get_cfp_auth_key();
    $authKey=$authKeyData['auth_key'];

    if(!$userBank||$userBank->account_name!=$accountName||$userBank->account_number!=$accountNumber||$userBank->ifsc_code!=$ifscCode){
        
        if($userBank){
            $removedBeneData=$this->remove_beneficiary($userId,$authKeyData);
        }
    }else{
        $result=[];
        $result['auth']=$authKeyData;
        $result['bank']=$userBank;
        return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$result]);
    }

    
    

    $post_data = json_encode($beneficiaryData, JSON_UNESCAPED_SLASHES);

    $url = "https://payout-api.cashfree.com/payout/v1/addBeneficiary";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $headers=['Content-Type: application/json',
                'Authorization: Bearer '.$authKey];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    $response = curl_exec($ch);

    $responseData=json_decode($response,JSON_UNESCAPED_SLASHES);
    

    if($responseData['subCode']==200&&$responseData['status']=="SUCCESS"){
        if($userBank){
            $userBank->account_number=$accountNumber;
            $userBank->ifsc_code=$ifscCode;
            $userBank->account_name=$accountName;
            $userBank->save();
        }else{
            $userBankData=[];
            $userBankData['user_id']=$userId;
            $userBankData['account_number']=$accountNumber;
            $userBankData['ifsc_code']=$ifscCode;
            $userBankData['account_name']=$accountName;
            
            $userBank=UserBank::create($userBankData);
        }
            
        $result=[];
        $result['auth']=$authKeyData;
        $result['bank']=$userBank;

        return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$userBank]);
    }

    return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
}

public function remove_beneficiary($beneId,$authKeyData){
    $beneficiaryData=[];
    $beneficiaryData['beneId']=$beneId;
    $authKey=$authKeyData['auth_key'];

    $post_data = json_encode($beneficiaryData, JSON_UNESCAPED_SLASHES);

    $url = "https://payout-api.cashfree.com/payout/v1/removeBeneficiary";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $headers=['Content-Type: application/json',
                'Authorization: Bearer '.$authKey];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    $response = curl_exec($ch);

    $responseData=json_decode($response,JSON_UNESCAPED_SLASHES);

    return $responseData;
}


public function get_cfp_transfer_status(Request $request){
    DB::beginTransaction();

    try{

    $authKeyData=$this->get_cfp_auth_key();
    $authKey=$authKeyData['auth_key'];

    $transaction=Transaction::where('order_id',$request->transfer_id)->first();

    $url = "https://payout-api.cashfree.com/payout/v1.1/getTransferStatus?referenceId=".$request->reference_id."&transferId=".$request->transfer_id;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $headers=['Content-Type: application/json',
                'Authorization: Bearer '.$authKey];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    $response = curl_exec($ch);

    $responseData=json_decode($response,JSON_UNESCAPED_SLASHES);
        
    if($transaction->txn_status=="PENDING"){
        if($responseData['subCode']==200&&$responseData['status']=="SUCCESS"){
            if($responseData['data']['transfer']['status']=="SUCCESS"){$transaction->txn_status="SUCCESS";
                $amount=$transaction->txn_amount;
                $user=User::where('id',$transaction->user_id)->first();
                
                $transaction->txn_status="SUCCESS";
                $transaction->save();

                // $title="Successful Withdrawal";
                // $message="Amount of Rs ".$amount.' is withdrawn successfully.';
        

                // $tokenList=[];
                // array_push($tokenList,$user->token);
                // $this->notification($tokenList,$title,$message,$setting->gcm_auth);

            }else if($responseData['data']['transfer']['status']=="FAILED"){

                $user=User::where('id',$transaction->user_id)->first();

                $amount=$responseData['data']['transfer']['amount'];

                $transaction->txn_status="FAILED";
                $transaction->closing_balance=$transaction->closing_balance+$amount;
                $user->wallet_balance=$user->wallet_balance+$amount;
                $transaction->save();
                $user->save();

                // $title="Failed Withdrawal";
                // $message="Failed to withdraw amount of Rs ".$amount;
        

                // $tokenList=[];
                // array_push($tokenList,$user->token);
                // $this->notification($tokenList,$title,$message,$setting->gcm_auth);

            }
        }
    }
    DB::commit();
    return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);

    }catch(Exception $e){
        DB::rollBack();
        return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
    }

}


public function cfp_transfer_webhook(Request $request){
    $setting=Setting::first();
    $secretKey=$setting->cfp_secret_key;

    $data = $request->all();
    $signature = $data["signature"];
    unset($data["signature"]);
    ksort($data);
    $postData = "";
    foreach ($data as $key => $value){
        if (strlen($value) > 0) {
        $postData .= $value;
        }
    }
    $hash_hmac = hash_hmac('sha256', $postData, $secretKey, true) ;
    
    $computedSignature = base64_encode($hash_hmac);
    if ($signature == $computedSignature) {

        DB::beginTransaction();

        try{
            if($data['event']=="LOW_BALANCE_ALERT"){
                return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
            }else if($data['event']=="CREDIT_CONFIRMATION"){
                return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
            }else if($data['event']=="TRANSFER_SUCCESS"||$data['event']=="TRANSFER_ACKNOWLEDGED"){
                //Transaction successful
                $transaction=Transaction::where('order_id',$data['transferId'])->first();
                if($transaction['txn_status']=="PENDING"){
                $amount=$transaction->txn_amount;
                $user=User::where('id',$transaction->user_id)->first();

                $transaction->txn_status="SUCCESS";
                $transaction->save();

                // $title="Successful Withdrawal";
                // $message="Amount of Rs ".$amount.' is withdrawn successfully.';
        

                // $tokenList=[];
                // array_push($tokenList,$user->token);
                // $this->notification($tokenList,$title,$message,$setting->gcm_auth);
                }
            }else{
                //Failed transaction
                $transaction=Transaction::where('order_id',$data['transferId'])->first();
                if($transaction['txn_status']=="PENDING"){

                $user=User::where('id',$transaction->user_id)->first();

                $amount=$transaction->txn_amount;

                $transaction->txn_status="FAILED";
                $transaction->closing_balance=$transaction->closing_balance+$amount;
                $user->wallet_balance=$user->wallet_balance+$amount;
                $transaction->save();
                $user->save();

                // $title="Failed Withdrawal";
                // $message="Failed to withdraw amount of Rs ".$amount;
        

                // $tokenList=[];
                // array_push($tokenList,$user->token);
                // $this->notification($tokenList,$title,$message,$setting->gcm_auth);
                }
            }
                
            DB::commit();
            return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
        }
        
    } else {
        // Reject this call
        return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
    }
}


//21. get user bank details
public function get_bank_details($userId){
    $bankDetails=UserBank::where('user_id',$userId)
                        ->first();

    return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$bankDetails]);        
}


//22. get user subscriptions
public function get_user_subscription($userId){
    $user=User::where('id',$userId)
            ->first();

    if($user){
        $result=[];
        $result['is_premium']=$user->is_premium;
        $result['expiry_date']=$user->subscription_ends;

        return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$result]);        
    }

    return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);        
}


//23. buy subscriptions (cashfree)
public function create_cashfree_token(Request $request){
    $userId=$request->user_id;
    $orderId=$request->order_id;
    $orderAmount=$request->order_amount;
    $subsType=$request->subs_type;
    $cashfreeParam['orderId']=$orderId;
    $cashfreeParam['orderAmount']=$orderAmount;
    $cashfreeParam['orderCurrency']="INR";

    $now=new Carbon();
    $expiryDate=$now;

    if($subsType=="MONTHLY"){
        $now->addMonth(1);
        $expiryDate=$now->toDateTime();
    }else{
        $now->addYear(1);
        $expiryDate=$now->toDateTime();
    }

    $setting=Setting::first();
    $appId=$setting->cf_app_id;
    $secretKey=$setting->cf_secret_key;

    $post_data = json_encode($cashfreeParam, JSON_UNESCAPED_SLASHES);

    $url = "https://api.cashfree.com/api/v2/cftoken/order";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $headers=['Content-Type: application/json',
                'x-client-id: '.$appId,
                'x-client-secret: '.$secretKey];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    $response = curl_exec($ch);

    $responseData=json_decode($response,JSON_UNESCAPED_SLASHES);

    if($responseData['status']=="OK"){
        DB::beginTransaction();
        try{

            $user=User::where('id',$userId)
                        ->first();

            $transactionData=[];
            $transactionData['order_id']=$orderId;
            $transactionData['user_id']=$userId;
            $transactionData['txn_mode']="CASH-FREE";
            $transactionData['txn_type']="SUBSCRIPTION";
            $transactionData['txn_status']="PENDING";
            $transactionData['txn_title']="Purchase subscription";
            $transactionData['txn_amount']=$orderAmount;
            $transactionData['closing_balance']=$user->wallet_balance;
    
            $transaction=Transaction::create($transactionData);

            $subscriptionData=[];
            $subscriptionData['user_id']=$userId;
            $subscriptionData['txn_id']=$transaction->id;
            $subscriptionData['subs_amount']=$orderAmount;
            $subscriptionData['expiry_date']=$expiryDate;
            $subscriptionData['subs_type']=$subsType;
            $subscriptionData['is_active']=0;

            $subscription=Subscription::create($subscriptionData);

            DB::commit();
            return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$responseData]);
        }catch(Exception $e){
            DB::rollBack();
            return $e;
        }
    }

    return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
}


public function cashfree_webhook(Request $request){

    $setting=Setting::first();
    $secretKey=$setting->cf_secret_key;

    $orderId = $request->orderId;
    $orderAmount = $request->orderAmount;
    $referenceId = $request->referenceId;
    $txStatus = $request->txStatus;
    $paymentMode = $request->paymentMode;
    $txMsg = $request->txMsg;
    $txTime = $request->txTime;
    $signature = $request->signature;
    $data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
    $hash_hmac = hash_hmac('sha256', $data, $secretKey, true) ;
    $computedSignature = base64_encode($hash_hmac);
    if ($signature == $computedSignature) {

        DB::beginTransaction();

        try{
            $transaction=Transaction::where('order_id',$orderId)->first();
        if($transaction->txn_status=="PENDING"){
            $user=User::where('id',$transaction->user_id)->first();
            if($txStatus=="SUCCESS"){
                //Transaction is successful
                $transaction->reference_id=$referenceId;
                $transaction->txn_status="SUCCESS";
                $transaction->save();

                $subscription=Subscription::where('txn_id',$transaction->id)
                                        ->first();


                if($subscription&&$subscription->is_active==0){
                    $subsType=$subscription->subs_type;
                    $now=new Carbon();
                    $expiryDate=$now;

                    if($subsType=="MONTHLY"){
                        $now->addMonth(1);
                        $expiryDate=$now->toDateTime();
                    }else{
                        $now->addYear(1);
                        $expiryDate=$now->toDateTime();
                    }

                    $subscription->is_active=1;
                    $subscription->expiry_date=$expiryDate;
                    $subscription->save();

                    $user->is_premium=1;
                    $user->subscription_ends=$expiryDate;
                    $user->save();
                }

        
            }else if($txStatus=="FAILED"){
                //Transaction is failed
                $transaction->reference_id=$referenceId;
                $transaction->txn_status="FAILED";
                $transaction->save();

            }else if($txStatus=="CANCELLED"){
                //Transaction is cancelled
                $transaction->reference_id=$referenceId;
                $transaction->txn_status="FAILED";
                $transaction->save();

            }
        }

        DB::commit();

        return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);

        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
        }
        
   } else {
      // Reject this call
      return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
    }
}


public function verify_cashfree_signature(Request $request){

    $setting=Setting::first();
    $secretKey=$setting->cf_secret_key;

    $orderId = $request->orderId;
     $orderAmount = $request->orderAmount;
     $referenceId = $request->referenceId;
     $txStatus = $request->txStatus;
     $paymentMode = $request->paymentMode;
     $txMsg = $request->txMsg;
     $txTime = $request->txTime;
     $signature = $request->signature;
     $data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
     $hash_hmac = hash_hmac('sha256', $data, $secretKey, true) ;
     $computedSignature = base64_encode($hash_hmac);
     if ($signature == $computedSignature) {
        DB::beginTransaction();

        try{
            $transaction=Transaction::where('order_id',$orderId)->first();
            if($transaction->txn_status=="PENDING"){
                $user=User::where('id',$transaction->user_id)->first();
                if($txStatus=="SUCCESS"){
                    //Transaction is successful
                    $transaction->reference_id=$referenceId;
                    $transaction->txn_status="SUCCESS";
                    $transaction->save();
    
                    $subscription=Subscription::where('txn_id',$transaction->id)
                                            ->first();
    
    
                    if($subscription&&$subscription->is_active==0){
                        $subsType=$subscription->subs_type;
                        $now=new Carbon();
                        $expiryDate=$now;
    
                        if($subsType=="MONTHLY"){
                            $now->addMonth(1);
                            $expiryDate=$now->toDateTime();
                        }else{
                            $now->addYear(1);
                            $expiryDate=$now->toDateTime();
                        }
    
                        $subscription->is_active=1;
                        $subscription->expiry_date=$expiryDate;
                        $subscription->save();
    
                        $user->is_premium=1;
                        $user->subscription_ends=$expiryDate;
                        $user->save();
                    }
    
            
                }else if($txStatus=="FAILED"){
                    //Transaction is failed
                    $transaction->reference_id=$referenceId;
                    $transaction->txn_status="FAILED";
                    $transaction->save();
    
                }else if($txStatus=="CANCELLED"){
                    //Transaction is cancelled
                    $transaction->reference_id=$referenceId;
                    $transaction->txn_status="FAILED";
                    $transaction->save();
    
                }
        }
        DB::commit();
        return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$transaction]);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
        }
    } else {
       // Reject this call
       return response()->json(['status'=>'FAILURE','code'=>'FC_01','data'=>null]);
     }
}


//24. get refer details and create share link


//25. get informations
public function get_information(){
    $setting=Setting::select('privacy_policy','tnc','about_us','contact_us','monthly_subs_price','yearly_subs_price','from_refer_amount','to_refer_amount')
                    ->first();

    return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$setting]);
}


//26. get chat messages & user details
public function get_chat_messages(Request $request){
    $chatId=$request->chat_id;
    $fromId=$request->from_id;
    $limit=$request->limit;

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

    return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$messages]);
}


//27. send message to user
public function send_message_user(Request $request){
    $chatId=$request->chat_id;
    $adsId=$request->ads_id;
    $senderId=$request->sender_id;
    $receiverId=$request->receiver_id;
    $message=$request->message;
    $isImage=$request->is_image;

    

    if($chatId==-1){
        $chatData=[];
        $chatData['seller_user_id']=$receiverId;
        $chatData['buyer_user_id']=$senderId;
        $chatData['ads_id']=$adsId;
        $chatData['blocked_id']=-1;

        $chat=Chat::create($chatData);

        $chatId=$chat->id;
    }

    $chat=Chat::where('id',$chatId)
                ->first();

    if($chat->blocked_id==$chat->seller_user_id||$chat->blocked_id==$chat->buyer_user_id){
        return response()->json(['status'=>'FAILED','code'=>'FC_01','data'=>null]);
    }

    
    $messageData=[];
    $messageData['chat_id']=$chatId;
    $messageData['sender_id']=$senderId;
    $messageData['message']=$message;
    $messageData['is_image']=$isImage;
    $messageData['image_link']="-";
    $messageData['is_seen']=0;

    if($isImage==1){
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $fname = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('/Chats');
            $image->move($destinationPath, $fname);
    
            $messageData['image_link']=$fname;
        }
    }

    $message=Message::create($messageData);

    //send data to socket
    return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$message]);
}

//DONE //28. upload image and send message data


//29. block a user //optional
public function block_user_chat(Request $request){
    $blockedId=$request->blocked_id;
    $chatId=$request->chat_id;

    $chat=Chat::where('id',$chatId)
            ->first();

    if($chat){
        if($chat->blocked_id==$chat->seller_user_id||$chat->blocked_id==$chat->buyer_user_id){
            $chat->blocked_id=-1;
        }else{
            $chat->blocked_id=$blockedId;
        }
        $chat->save();
    }
    return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$chat]);
}


public function get_all_categories(){
    $categories=Category::where('is_active',1)
                    ->orderBy('created_at','ASC')
                    ->get();

    return response()->json(['status'=>'SUCCESS','code'=>'SC_01','data'=>$categories]);                
}

}
