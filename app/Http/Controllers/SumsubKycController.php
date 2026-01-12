<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\User;
use App\Models\SumsubKyc;

class SumsubKycController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware(['auth','twofa']);
    }

    public function index()
    {
        $userId = Auth::user()->id;
        $userEmail = Auth::user()->email;
        $userId = 'WallexPay'.$userId;
        
        //$userId = rand(1000000000,9999999999);
        //$userEmail = 'gk'.$userId.'@dummy.com';
       
        $kycdata = SumsubKyc::where('email',$userEmail)->first();
        if(is_null($kycdata)){        
            $applicantRequests = $this->createApplicant($userId,$userEmail);
            //dd($applicantRequests);
            if(!empty($applicantRequests) && $applicantRequests['id']){        
                $kyc = new SumsubKyc;
                $kyc->kyc_id = $applicantRequests['id'];
                $kyc->kycrequest_id = $applicantRequests['id'];
                $kyc->key = $applicantRequests['key'];
                $kyc->clientId = $applicantRequests['clientId'];
                $kyc->inspectionId = $applicantRequests['inspectionId'];
                $kyc->externalUserId = $applicantRequests['externalUserId'];
                $kyc->uid = Auth::user()->id;
                $kyc->refuid = $userId;
                $kyc->email = $userEmail;
                $kyc->save();
                $accessTokens = $this->accessTokens($userId);
                return view('kycverify',['token' => $accessTokens]);
        	}else{
                \Session::flash('error', 'Token not Generated');
                return redirect()->back();
        	}
        }else{
            $accessTokens = $this->accessTokens($userId);
            if(isset($accessTokens['token'])){
                return view('kycverify',['token' => $accessTokens]);
            }else{
                dd($accessTokens);
                return view('kycverify',['token' => $accessTokens]);
            }
            
        }
    }

    public function createApplicant($UserId,$email)
    {
        $role = Auth::user()->role;
        if($role == 'Personal'){
            $level = "wallex_pay_freelance";
        }else{
            $level = "crypto_processor_kyb_2023";
        }
        
        $method= 'POST';
        $url="/resources/applicants?levelName=$level";
        $payload = json_encode(array("externalUserId" => $UserId,"email" => $email));
        $headers = $this->signature($url,$method,$payload);
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.sumsub.com'.$url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => $payload,
          CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response,true);
    }


    function applicantRequests($userId,$userEmail){
        $role = Auth::user()->role;
        if($role == 'Personal'){
            $level = "wallex_pay_freelance";
        }else{
            $level = "crypto_processor_kyb_2023";
        }
        $method='POST';
        $url="/resources/accessTokens?userId=$userId&levelName=$level";        
        $payload = '';
        $headers = $this->signature($url,$method,$payload);
        if($headers){
            $token = $this->generateToken($headers,$url,$payload);
            if($token){
                return $token;
            }else{
                return false;  
            }
        }else{
            return false;
        }
    }

    function accessTokens($userId){
        $method='POST';
        $payload = '';
        $role = Auth::user()->role;
        if($role == 'Personal'){
            $level = "wallex_pay_freelance";
        }else{
            $level = "crypto_processor_kyb_2023";
        }
        $url="/resources/accessTokens?userId=$userId&levelName=$level";
        $headers = $this->signature($url,$method,$payload=null);
        if($headers){
        $token = $this->generateAuthToken($url,$headers);
        if($token){
        return $token;
        }else{
        return false;  
        }
        }else{
        return false;  
        }
    }
    
    function ajaxkyc(Request $request){
    $userEmail = Auth::user()->email;
    $kycdata = SumsubKyc::where('email',$userEmail)->first();
    if($kycdata){
        $kycdata->kyc_id = $request->data;
        $kycdata->status = 3;
        $kycdata->save();
    }
    $data['sucess'] = 'success';
    return $data;
    }
    
    function kycstatus(){
     
        //$userId = Auth::user()->id;
        //$userEmail = Auth::user()->email;
        $kycdatas = SumsubKyc::where('status',3)->orWhere('status',0)->get();
        
        if($kycdatas){

        foreach ($kycdatas as $key => $kycdata) {
            # code...
        
        $userId = $kycdata->uid;
        $userEmail = $kycdata->email;

        $kyc_id = $kycdata->kyc_id;;
        $method='GET';
        $payload = '';
        $url='/resources/applicants/'.$kyc_id.'/status'; //for get status        
        //$url='/resources/applicants/'.$kyc_id; //for get full data
        
        $headers = $this->signature($url,$method,$payload);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sumsub.com'.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            //echo 'Error:' . curl_error($ch);
            return redirect('/profile')->with('error', 'Bad request!');
        }
        curl_close($ch);
        $array = json_decode($result , true);

        $status = 0; 
        if(!empty($array['reviewStatus'])){
        $remark = $array['reviewStatus'];
        if(($array['reviewStatus'] == 'pending') || ($array['reviewStatus'] == 'queued'))
        { 
            $status = 3; 
            $userStatus = 2; 
        }
        if($array['reviewStatus'] == 'init'){ $status = 0; }
        if($array['reviewStatus'] == 'completed'){ 
            if($array['reviewResult']['reviewAnswer'] == 'RED'){                
            $status = 2;
            if(!empty($array['reviewResult']['moderationComment'])){
            $remark = $array['reviewResult']['moderationComment'];  
            }          
            $userStatus = 3; 
            }
            if($array['reviewResult']['reviewAnswer'] == 'GREEN'){                
            $status = 1;            
            $userStatus = 1; 
            }
        }

        $kycdata->remark = $remark;
        $kycdata->status = $status;
        $kycdata->save();

        $userdata = User::where('email',$userEmail)->first();
        if($userdata){
        $userdata->kyc_verify = $userStatus;
        $userdata->save();            
        }
      }
    }
        return redirect('/profile');
        }else{
            return redirect('/profile')->with('error', 'Bad request!');
        }
    }

    function signature($endpoint, $method, $payload)
    {    
        //$appToken = 'sbx:PtdKZPVLv2yJGrQEyuvtqpr2.Rjw02Zeb0hrAHZ13tyeiCAF7tkO9FTag';
        //$secretKey = 'poms7ENmfQjqUe94R5PAkL8ea8jakHjP';

        $appToken = 'prd:GClm3b278nnJTZ3wtyFPkt2r.JNAUMniVPSjXvYNqCVC1nezafMS7iFBw';
        $secretKey = 'YXTROddK4Zvu5plHc4IjjaadgrlRvMcH';
        $ts = round(time());
        //$ts = (string)$ts;
        if(is_null($payload)){
            $signature = hash_hmac('sha256', $ts . $method . $endpoint, $secretKey);
        }else{
            $signature = hash_hmac('sha256', $ts . $method . $endpoint . $payload, $secretKey);
        }
        $headers = [
        'Accept: application/json',
        'X-App-Token:' . $appToken,
        'X-App-Access-Sig:' . $signature,
        'X-App-Access-Ts:' . $ts,
        'Content-Type: application/json'
        ];
        return $headers;
    }

    function generateToken($headers, $url, $payload)
    {
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result , true);
    }

    function generateAuthToken($url,$headers){
       $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.sumsub.com$url",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_HTTPHEADER => $headers,
        ));

        $result = curl_exec($curl);

        curl_close($curl);
        return json_decode($result , true);
    }
}
