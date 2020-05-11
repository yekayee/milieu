<?php
/**	
 * Milieu-bot
 * 
 * @release 2020
 * @author eco.nxn
 */
date_default_timezone_set("Asia/Jakarta");
error_reporting(0);
class curl {
	private $ch, $result, $error;
	
	/**	
	 * HTTP request
	 * 
	 * @param string $method HTTP request method
	 * @param string $url API request URL
	 * @param array $param API request data
     * @param array $header API request header
	 */
	public function request ($method, $url, $param, $header) {
		curl:
        $this->ch = curl_init();
        switch ($method){
            case "GET":
                curl_setopt($this->ch, CURLOPT_POST, false);
                break;
            case "POST":               
                curl_setopt($this->ch, CURLOPT_POST, true);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $param);
                break;
        }
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'okhttp/3.12.1');
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 20);
        $this->result = curl_exec($this->ch);
        $this->error = curl_error($this->ch);
        if($this->error) {
            echo "[!] ".date('H:i:s')." | Connection Timeout\n";
            sleep(2);
            goto curl;
        }
        curl_close($this->ch);
        return $this->result;
    }   
}

class milieu extends curl{

    function random($length)
    {
        $data = 'qwertyuioplkjhgfdsazxcvbnm0123456789';
        $string = '';
        for($i = 0; $i < $length; $i++) {
            $pos = rand(0, strlen($data)-1);
            $string .= $data{$pos};
        }
        return $string;
    }

    function random_str($length)
    {
        $data = 'qwertyuioplkjhgfdsazxcvbnmMNBVCXZASDFGHJKLPOIUYTREWQ';
        $string = '';
        for($i = 0; $i < $length; $i++) {
            $pos = rand(0, strlen($data)-1);
            $string .= $data{$pos};
        }
        return $string;
    }

    /**
     * Get random name
     */
    function randomuser($qty) {
        randomuser:
        $randomuser = file_get_contents('https://econxn.id/api/v1/randomUser/?quantity='.$qty);
        if($randomuser) {
            $json = json_decode($randomuser);
            if($json->status->code == 200) {
                return $json->result;
            } else {
                echo "[!] ".date('H:i:s')." | Failure while generating name!\n";
                sleep(2);
                goto randomuser;
            }        
        } else {        
            sleep(2);
            goto randomuser;
        }
    }

    /**
     * Registrasi akun
     */
    function regis($first_name, $last_name, $email, $pass, $device_id, $header) { 

        $method   = 'POST';
        $header[] = 'Content-Type: application/json;charset=utf-8';

        $endpoint = 'https://api-prod-id.mili.eu/api/v3/user/consumer/register';
        
        $param = '{"email":"'.$email.'","password":"'.$pass.'","name_first":"'.$first_name.'","name_last":"'.$last_name.'","device_id":"'.$device_id.'","client_end":"mobile_consumer"}';
        
        $regis = $this->request ($method, $endpoint, $param, $header);

        $json = json_decode($regis);

        if(!isset($json->user->id)) {
            if($json->frontend_type == 'BAD_DOMAIN') {
                echo "[!] ".date('H:i:s')." | Domain email ".$email." Banned..\n\n";
                die();
            }
            return FALSE;
        } else {
            return $json;
        }         
    }

    /**
     * Input Referal code
     */
    function reff($reff_code, $bearer, $header) { 

        $method   = 'POST';
        $header[] = 'Content-Type: application/json;charset=utf-8';
        $header[] = 'authorization: '.$bearer;

        $endpoint = 'https://api-prod-id.mili.eu/api/v3/user/onboard/me';
        
        $gender = ['male', 'female'];

        $param = '{"country_id":102,"state_id":1672,"referral_code":"'.$reff_code.'","birthday":"'.rand(10, 28).'-0'.rand(1, 9).'-'.rand(1994, 2002).'","gender":"'.$gender[rand(0,1)].'","income_level_id":'.rand(6,8).'}';
        $rf=0;
        reff:
        $reff = $this->request ($method, $endpoint, $param, $header);

        $json = json_decode($reff);

        if(!isset($json->id)) {
            if($json->statusCode == 429) {
                sleep(5);
                $rf = $rf+1;
                if($rf<=10) {
                    goto reff;
                } else {
                    echo "[!] ".date('H:i:s')." | Rate limit exceeded, santuy..\n";
                }
            } 
            return FALSE;
        } else {
            return $json;
        }         
    }

    /**
     * Login akun
     */
    function login($email, $pass, $dev_id, $dev_brand, $dev_model, $header) { 

        $method   = 'POST';
        $header[] = 'Content-Type: application/json;charset=utf-8';

        $endpoint = 'https://api-prod-id.mili.eu/api/v3/user/login';
        
        $param = '{"email":"'.$email.'","password":"'.$pass.'","device_id":"'.$dev_id.'","client_end":"mobile_consumer"}';
        
        $login = $this->request ($method, $endpoint, $param, $header); 

        $json = json_decode($login);

        if(!isset($json->user->id)) { 
            echo "[!] ".date('H:i:s')." | Login Gagal. ".$json->frontend_type."\n\n"; 

            if($json->frontend_type == 'ACCOUNT_SUSPENDED') {
                // save
                $fh = fopen('suspended.CSV', "a");
                fwrite($fh, date('d-m-Y H:i')." WIB;".$email.";".$pass.";".$dev_id."-".$dev_brand."-".$dev_model."\n");
                fclose($fh);
            } else {
                // save
                $fh = fopen('new_accounts.CSV', "a");
                fwrite($fh, $email.";".$pass.";".$dev_id."-".$dev_brand."-".$dev_model."\n");
                fclose($fh);
            }

            return FALSE;
        } else {
            return $json;
        }         
    }

    /**
     * Profile info
     */
    function profile($bearer, $header) { 

        $method   = 'GET';
        $header[] = 'authorization: '.$bearer;

        $endpoint = 'https://api-prod-id.mili.eu/api/v3/user/me';
        
        $profile = $this->request ($method, $endpoint, $param=NULL, $header);

        $json = json_decode($profile);

        if(!isset($json->id)) {
            return FALSE;
        } else {
            return $json;
        }         
    }

    /**
     * Generate new email
     */
    function new_email($user) {

        $method   = 'POST';
        $header   =  [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With: XMLHttpRequest'
        ];
        $endpoint = 'https://www.disposablemail.com/index/new-email/';

        $param = 'emailInput='.$user.'&format=json';

        $create_email = $this->request ($method, $endpoint, $param, $header); 

        if(is_numeric(strpos($create_email, '"ok"'))) {

            $method_   = 'GET';
            $header_   =  [
                'X-Requested-With: XMLHttpRequest'
            ];
            $endpoint_ = 'https://www.disposablemail.com/index/index';
            $get_email = $this->request ($method_, $endpoint_, $param_=NULL, $header_);

            $json = json_decode(json_decode(str_replace('\ufeff', '', json_encode($get_email)))) ;
            if(isset($json->email)) {
                $email = $json->email; 
                return $email;
            } else {
                return FALSE;
            }
            
        } else {
            return FALSE;
        }
    }

    /**
     * Inbox email
     */
    function inbox() {

        $method   = 'GET';
        $header   =  [
            'X-Requested-With: XMLHttpRequest'
        ];
        $endpoint = 'https://www.disposablemail.com/index/refresh';

        $inbox = $this->request ($method, $endpoint, $param=NULL, $header);

        $json_dec = json_decode(json_decode(str_replace('\ufeff', '', json_encode($inbox)))) ;

        foreach ($json_dec as $json) {  
            
            if(is_numeric(strpos($json->od, 'hello@mili.eu'))) {  

                $inbox_link =  'https://www.disposablemail.com/email/id/'.$json->id;

                $method_   = 'GET';
                $get_inbox = $this->request ($method_, $inbox_link, $param=NULL, $header);

                $a = stripos($get_inbox, 'email=');
                $b = strpos($get_inbox, '" target="_blank"');
                $activation_link = 'http://api-prod-id.mili.eu/api/1/verify?email='.substr($get_inbox, ($a+6), (strlen($get_inbox)-$b)*-1);
                return str_replace('&amp;', '&',$activation_link);
            } else {
                return FALSE;
            }
        }
    }

    /**
     * Activation
     */
    function activation($endpoint, $bearer, $header) {

        $method   = 'GET'; 
        
        $activation = $this->request ($method, $endpoint, $param=null, $header=null);

        $json = json_decode($activation);
        if(isset($json->error)) {
            return FALSE;
        } else {
            $profile = $this->profile($bearer, $header);
            if($profile == FALSE) {
                return FALSE;
            } else {
                $status = $profile->status;
                if($status == 'active') {
                    return TRUE;
                } else {
                    return FALSE;
                }
            }
        }
    }

    /**
     * Check Survey
     */
    function check_survey($bearer, $header) {
        $method   = 'GET';
        $header[] = 'authorization: '.$bearer;

        $endpoint = 'https://api-prod-id.mili.eu/api/v3/survey/user';
        
        $survey = $this->request ($method, $endpoint, $param=NULL, $header);

        $json = json_decode($survey, TRUE);

        if($json['surveys'] == []) {
            return FALSE;
        } else {
            $surveys = $json['surveys'];
            $data    = $json['surveys'][count($surveys)-1];
            $detail  = json_decode(json_encode($data));    

            $survey_id = $detail->id;
            if($survey_id > 6) {
                echo "[i] Survey id-".$survey_id." belum dianalisis\n\n";
                sleep(5);
            }
            return $json;
        }
    }

    /**
     * Check Anti-bot Questions
     */
    function check_antiBot_questions($json) { 

        $surveys = $json['surveys'];
        $data    = $json['surveys'][count($surveys)-1];

        $detail = json_decode(json_encode($data));
            
        $survey_id = $detail->id;
        $questions = $detail->questions;

        foreach ($questions as $Qvalue) {
            $question_id      = $Qvalue->id;
            $question_content = $Qvalue->content;
            $question_options = $Qvalue->options;  

            if (is_numeric(strpos($question_content, 'Kami memiliki pemeriksaan perhatian dalam survei kami')) ||
                is_numeric(strpos($question_content, 'Manakah dari pernyataan-pernyataan berikut ini yang benar')) ||
                is_numeric(strpos($question_content, 'Apakah Anda pernah ke bulan dalam dua bulan terakhir')) ||
                is_numeric(strpos($question_content, 'Manakah dari berikut ini yang bukan hewan?'))
            ) {
                print '[Q] Anti-bot Question: '.$question_content."\n";
                print "[+] Options:\n";  
                foreach ($question_options as $Ovalue) {   
                    print "    [ID:".$Ovalue->id."] Answer: ".$Ovalue->content."\n";  
                }  
                answer:
                print '[?] Answer ID :';
                $antibotAnswer = trim(fgets(STDIN));
                if(!empty($antibotAnswer)) {
                    echo "\n";

                    $antiBot['q'] = $question_id;
                    $antiBot['a'] = $antibotAnswer;
                    return $antiBot;
                } else {
                    goto answer;
                }
            }
        }   
    }

    /**
     * Setting Survey response
     */
    function response ($antiBot, $json) { 

        $makanan_khas = ['Sate','Bakso','Soto','Nasi Goreng','Gado-gado','Nasi Uduk','Nasi Padang','Ayam Goreng','Bakmi','Gudeg','Rawon','Pecel Lele','Opor Ayam','Gulai','Bubur Ayam','Asinan','Pepes','Pempek','Perkedel','Sayur Asem','Sop Buntut','Ketoprak','Lontong','Ketupat','Rendang','Tahu Gejrot','Sop Kambing','Otak-otak','Gorengan','Nasi Pecel','Karedok','Betutu','Serabi','Kolak','Lemang','Batagor','Kerak Telor','Kerupuk','Nasi Campur','Woku','Semur Jengkol','Empal Gentong'];

        $surveys = $json['surveys'];
        $data    = $json['surveys'][count($surveys)-1];

        $detail = json_decode(json_encode($data));
            
        $survey_id = $detail->id;
        $questions = $detail->questions;

        foreach ($questions as $Qvalue) {
            $question_id      = $Qvalue->id;
            $question_content = $Qvalue->content;
            $question_options = $Qvalue->options;

            $count_opt = count($question_options);   
            $rand_question_options = $question_options[rand(0, $count_opt-1)];

            if($question_id == $antiBot[$survey_id]['q']) { 
                // // print
                // print '[Q] Anti-bot Question: '.$question_content."\n";
                
                $_a=0;
                foreach ($question_options as $Ovalue) {     
                    if($antiBot[$survey_id]['a'] == $Ovalue->id) {
                        $antiBotanswers = [
                            'survey_question_id' => $question_id,
                            'answer_id' => $antiBot[$survey_id]['a']
                        ];
                        $_a=1;
                    } 
                    
                    if($_a==0) {
                        $_answer = $rand_question_options->id;
                        $antiBotanswers = [
                            'survey_question_id' => $question_id,
                            'answer_id' => $_answer
                        ];
                    }
                }  

                // // print
                // foreach ($question_options as $_Ovalue) {
                //     if($_Ovalue->id == $antiBot['a']) {
                //         echo "[A] AntiBot-Answer: ".$Ovalue->content." [".$antiBot['a']."]\n\n";
                //     }
                // }
                $answers[] = $antiBotanswers;

            } else { 
                if($survey_id == 6) {
                    if($question_id == 157) {
                        $answers[] = [
                            "survey_question_id"=> $question_id,
                            "answer_id"=> 782
                        ];
                        $answers[] = [
                            "survey_question_id"=> $question_id,
                            "answer_id"=> 778
                        ];
                    } elseif($question_id == 158) {
                        $answers[] = [
                            "survey_question_id"=> $question_id,
                            "answer_id"=> 796
                        ];
                    } elseif($question_id == 159) {
                        $answers[] = [
                            "survey_question_id"=> $question_id,
                            "answer_id"=> $makanan_khas[rand(0,41)]
                        ];
                    } elseif($question_id == 162) {
                        $answers[] = [
                            "survey_question_id"=> $question_id,
                            "answer_id"=> 811
                        ];
                    } elseif($question_id == 168) {
                        $answers[] = [
                            "survey_question_id"=> $question_id,
                            "answer_id"=> 857
                        ];
                    } elseif($question_id == 174) {
                        $answers[] = [
                            "survey_question_id"=> $question_id,
                            "answer_id"=> 902
                        ];
                    } elseif($question_id == 175) {
                        $answers[] = [
                            "survey_question_id"=> $question_id,
                            "answer_id"=> 912
                        ];
                    } else {
                        $_answer = $rand_question_options->id;
                        $answers[] = [
                            "survey_question_id"=> $question_id,
                            "answer_id"=> $_answer
                        ];
                    } 

                } else {
                    $_answer = $rand_question_options->id;
                    $answers[] = [
                        "survey_question_id"=> $question_id,
                        "answer_id"=> $_answer
                    ];
                } 
                
                // // print
                // echo "[Q] ".$question_content."\n";
                // echo "[A] Random-Answer: ".$rand_question_options->content." [".$_answer."]\n\n";
            }
        }

        $response = [
            'survey_id' => $survey_id,
            'answers'   => $answers,
            'survey_start_date' => date("D M d Y H:i:s").' GMT+0700',
            'matched_quota_grouping' => null
        ];

        return json_encode($response);       
    }

    /**
     * Send response
     */
    function send_response($response, $bearer, $header) { 

        $method   = 'POST';
        $header[] = 'Content-Type: application/json;charset=utf-8';
        $header[] = 'authorization: '.$bearer;

        $endpoint = 'https://api-prod-id.mili.eu/api/1/survey/response';
              
        $send = $this->request ($method, $endpoint, $response, $header);

        $json = json_decode($send); 

        if(!isset($json->user_survey->id)) {
            return FALSE;
        } else {
            return $json;
        }        
    } 
}

/**
 * Running
 */
echo "Checking for Updates...";
$version = 'V1.3';
check_update:
$json_ver = json_decode(file_get_contents('https://econxn.id/setset/milieu-manual.json'));
echo "\r\r                       ";
if(isset($json_ver->version)) {
    if($version != $json_ver->version) {
        echo "\n".$json_ver->msg."\n\n";
        die();
    } else {
        echo "\n[?] Password :";
        $password = trim(fgets(STDIN));
        if($json_ver->hash != md5($password)) {
            die();
        }
    }
} else {
    goto check_update;
}

// style 
echo "\n"; 
echo " milieu surveys\n";
echo " v1.3       _  _  _\n";             
echo "           (_)| |(_)\n";             
echo " _ __ ___   _ | | _   ___  _   _\n"; 
echo "| '_ ` _ \ | || || | / _ \| | | |\n";
echo "| | | | | || || || ||  __/| |_| |\n";
echo "|_| |_| |_||_||_||_| \___| \__,_|\n";
echo "                      By @eco.nxn\n";
echo "\n";
echo "*Semua akun tersimpan di accounts.CSV\n";
echo "*Akun utama tersimpan juga di main_accounts.CSV\n";
echo "*Akun suspend tersimpan di suspended.CSV\n\n";

$milieu = new milieu();

$model = ['xiaomi-Mi Note 10', 'xiaomi-Mi Note 10 Pro', 'xiaomi-Redmi Note 7 Pro', 'xiaomi-Redmi Note 5' , 'xiaomi-Redmi Note 8 Pro', 'xiaomi-Redmi Note 8', 'samsung-Galaxy J2 Core', 'samsung-Galaxy A11', 'samsung-Galaxy A31', 'samsung-Galaxy M11', 'samsung-Galaxy A21', 'samsung-Galaxy A51 5G', 'samsung-Galaxy A71 5G', 'samsung-Galaxy A70', 'samsung-Galaxy A80', 'samsung-Galaxy M10'];

$header[0]  = 'x-milieu-app-version: 1.9.4';
$header[1]  = 'x-milieu-app-build: 892';
$header[2]  = 'x-milieu-app-country: ID';
$header[3]  = 'x-milieu-device-os: a';
$header[6]  = 'x-milieu-device-os-version: 9';
$header[7]  = 'x-milieu-device-emulator: false';
$header[8]  = 'x-milieu-remote-config-version: 4';
$header[9]  = 'x-milieu-limit-ad-tracking: false';
$header[10] = 'accept-language: id-ID';

start:
echo "[+] Options:\n";
echo "[1] Registrasi akun (utama/referal)\n";
echo "[2] Isi semua survey\n";
echo "[3] Cek akun siap redeem\n";
echo "[?] Choice: ";
$choice = trim(fgets(STDIN));
echo "\n"; 

switch ($choice) {
    case '1':
        # regis akun referal
        if(file_exists("accounts.CSV")) {
            $reff_accounts = explode("\n",str_replace("\r","",file_get_contents("accounts.CSV")));
            if(count($reff_accounts) >= 2000) {
                echo "\n[!] Akun referal telah mencapai batas limit.\n\n";
                die();
            }
        } 

        echo "[?] Registrasi Akun Utama? [Y/N] ";
        $_utama = trim(fgets(STDIN));
        if(strtolower($_utama) == 'y' ) {
            $akun_utama = TRUE;
            echo "[i] Kamu memilih registrasi Akun Utama\n";
        } else {
            $akun_utama = FALSE;
            echo "[i] Kamu memilih registrasi Akun Referal\n";
        }

        _qty:
        echo "[?] Jumlah akun :";
        $_qty = trim(fgets(STDIN));
        if(!is_numeric($_qty) || $_qty > 50) {
            echo "[!] Maksimal 50 akun!\n";
            goto _qty;
        } 

        echo "[?] Kode Referral :";
        $reff_code = trim(fgets(STDIN));
        echo "\n";

        $no=1;
        while($no <= $_qty) {

            $randomuser = $milieu->randomuser($_qty);
            foreach ($randomuser as $value) {
                $first_name = $value->Firstname;
                $last_name  = $value->Lastname;
                $exp_email  = explode("@", $value->Email); 
                $user_email = str_replace('.','', $exp_email[0]);
                $pass       = ucwords($milieu->random_str(9)).rand(1,9);
                $device_id  = $milieu->random(16);

                $x_device   = explode('-', $model[rand(0, 15)]);
                $header[4]   = 'x-milieu-device-brand: '.$x_device[0];
                $header[5]   = 'x-milieu-device-model: '.$x_device[1];

                echo "[i] Device: ".ucwords($x_device[0])." - ".$x_device[1]." [".$device_id."]\n";

                echo "[i] Buatlah email dengan username ".$user_email." atau yang lain!\n";
                echo "[?] Paste email kamu disini :";
                $email = trim(fgets(STDIN));
                $reg=0;
                register:
                $regis = $milieu->regis($first_name, $last_name, $email, $pass, $device_id, $header);
                if($regis == FALSE) {   
                    $reg = $reg+1;
                    if($reg<=3) {
                        goto register;
                    } else {
                        echo "[!] ".date('H:i:s')." | Registrasi Gagal, santuy..\n\n";
                    }  
                } else {
                    $bearer = $regis->token;
                    $regis_id = $regis->user->id;
                    $referral = $regis->user->referral_code;

                    $rf=0;
                    refer:
                    $_referal = $milieu->reff($reff_code, $bearer, $header);
                    if($_referal == FALSE) { 
                        $rf = $rf+1;
                        if($rf<=3) {
                            goto refer;
                        } else {
                            echo "[!] ".date('H:i:s')." | Gagal nge-Refer, santuy..\n\n";
                        }

                    } else {
                        if($akun_utama == TRUE) {
                            echo "[".$no++."] ".date('H:i:s')." | Registrasi Berhasil id-".$regis_id." [email:".$email."+pass:".$pass."+reff:".$referral."]\n";
                        } else {
                            echo "[".$no++."] ".date('H:i:s')." | Registrasi Berhasil id-".$regis_id." [email:".$email."-pass:".$pass."]\n";
                        } 

                        echo "[i] Email aktivasi telah dikirim, klik link aktivasi tersebut!\n";
                        echo "[?] Aktivasi email berhasil [Y/N] ";
                        $active = trim(fgets(STDIN));
                        if(strtolower($active) != 'y' ) {
                            echo "\n";
                            if($no > $_qty) {
                                die();
                            }
                            continue;
                        }

                        if($akun_utama == TRUE) {
                            // save
                            $fh = fopen('main_accounts.CSV', "a");
                            fwrite($fh, $email.";".$pass.";".$device_id."-".$x_device[0]."-".$x_device[1].";".$referral."\n");
                            fclose($fh);
                        } 
                        // save
                        $fh = fopen('accounts.CSV', "a");
                        fwrite($fh, $email.";".$pass.";".$device_id."-".$x_device[0]."-".$x_device[1].";".$bearer."\n");
                        fclose($fh);
                        echo "\n";
                        if($no > $_qty) {
                            die();
                        }
                    }
                }        
            }   
        }

        break;

    case '2':
        # isi surveys
        echo "[i] Menyiapkan survey..\n\n";
        if(file_exists('new_accounts.CSV')) {
            unlink('new_accounts.CSV');
        } elseif(file_exists('new_accounts.txt')) {
            unlink('new_accounts.txt');
        }

        if(file_exists("accounts.CSV")) {
            $list = explode("\n",str_replace("\r","",file_get_contents("accounts.CSV")));
        } elseif(file_exists("accounts.txt")) {
            $list = explode("\n",str_replace("\r","",file_get_contents("accounts.txt")));
        }

        file_get_contents('https://econxn.id/milieu.php?count='.count($list));
        
        $_no=1;$_r=1;$survey_id[] ='';
        foreach ($list as $value) {
            _start:
            if(empty($value)) {
                continue;
            }

            $exp_acc = explode(";", $value);
            $email  = $exp_acc[0];
            $pass   = $exp_acc[1];
            $bearer = $exp_acc[3];

            $exp_device = explode('-', $exp_acc[2]);
            $dev_id = $exp_device[0];

            if(empty($exp_device[1])) {
                $x_device   = explode('-', $model[rand(0, 15)]);
                $dev_brand = $x_device[0];
                $dev_model = $x_device[1];
            } else {
                $dev_brand = $exp_device[1];
                $dev_model = $exp_device[2];
            }

            $header[4]   = 'x-milieu-device-brand: '.$dev_brand;
            $header[5]   = 'x-milieu-device-model: '.$dev_model;

            if(file_exists('ready_to_redeem.CSV')) {
                unlink('ready_to_redeem.CSV');
            }

            if(is_numeric(strpos($bearer, 'Bearer'))) {
                $logged = $milieu->profile($bearer, $header);
                if($logged == FALSE) {
                    $login = $milieu->login($email, $pass, $dev_id, $dev_brand, $dev_model, $header);
                    if($login == FALSE) { 
                        //
                    } else {
                        $bearer = $login->token;

                        echo "[".$_no++."] Login sebagai id-".$login->user->id." [Points ".$login->user->points."]\n";
                        echo "[i] Device: ".ucwords($dev_brand)." - ".$dev_model." [".$dev_id."]\n";

                        $check_survey = $milieu->check_survey($bearer, $header);
                        if($check_survey == FALSE) {
                            echo "[!] Survey tidak tersedia saat ini\n\n";
                            if($login->user->points >= 11000) {
                                // save
                                $fh = fopen('ready_to_redeem.CSV', "a");
                                fwrite($fh, $email.";".$pass.";".$dev_id."-".$dev_brand."-".$dev_model.";;".$login->user->referral_code.";".$login->user->points." Point\n");
                                fclose($fh);
                            }
                        } else {
                            $check_antiBot = 1;
                            foreach ($survey_id as $survey_id_) {
                                if($survey_id_ == $check_survey['surveys'][0]['id']) {
                                    $check_antiBot = 0;
                                }
                            }

                            $survey_id[] = $check_survey['surveys'][0]['id'];

                            if($check_antiBot == 1) {
                                $check_antiBot_questions[$check_survey['surveys'][0]['id']] = $milieu->check_antiBot_questions($check_survey);  
                            }

                            $survey = $milieu->response ($check_antiBot_questions, $check_survey);

                            $isi_survey = $milieu->send_response($survey, $bearer, $header);
                            if($isi_survey == FALSE) {
                                echo "[!] Gagal kirim survey\n";
                                $_r++;
                                if($_r >= 3) {
                                    echo "[i] Hubungi Author\n\n";
                                    die();
                                }
                                sleep(2);
                                echo "[i] Mencoba kembali...\n\n";
                                $_c_ ='';
                                $_no--;
                                goto _start;
                            } else {
                                echo "[i] Berhasil kirim survey id-".$isi_survey->user_survey->survey_id." [Point saat ini: ".$isi_survey->user_points."]\n\n";
                            }
                        }  
                        
                        // save
                        $fh = fopen('new_accounts.CSV', "a");
                        fwrite($fh, $email.";".$pass.";".$dev_id."-".$dev_brand."-".$dev_model.";".$bearer."\n");
                        fclose($fh);       
                    }
                } else {

                    echo "[".$_no++."] Login sebagai id-".$logged->id." [Points ".$logged->points."]\n"; 
                    echo "[i] Device: ".ucwords($dev_brand)." - ".$dev_model." [".$dev_id."]\n";

                    $check_survey = $milieu->check_survey($bearer, $header);
                    if($check_survey == FALSE) {
                        echo "[!] Survey tidak tersedia saat ini\n\n";
                        if($logged->points >= 11000) {
                            // save
                            $fh = fopen('ready_to_redeem.CSV', "a");
                            fwrite($fh, $email.";".$pass.";".$dev_id."-".$dev_brand."-".$dev_model.";;".$logged->referral_code.";".$logged->points." Point\n");
                            fclose($fh);
                        }
                    } else {
                        $check_antiBot = 1;
                        foreach ($survey_id as $survey_id_) {
                            if($survey_id_ == $check_survey['surveys'][0]['id']) {
                                $check_antiBot = 0;
                            }
                        } 
                        
                        $survey_id[] = $check_survey['surveys'][0]['id'];

                        if($check_antiBot == 1) {
                            $check_antiBot_questions[$check_survey['surveys'][0]['id']] = $milieu->check_antiBot_questions($check_survey);  
                        }

                        $survey = $milieu->response ($check_antiBot_questions, $check_survey);

                        $isi_survey = $milieu->send_response($survey, $bearer, $header);
                        if($isi_survey == FALSE) {
                            echo "[!] Gagal kirim survey\n";
                            $_r++;
                            if($_r >= 3) {
                                echo "[i] Hubungi Author\n\n";
                                die();
                            }
                            sleep(2);
                            echo "[i] Mencoba kembali...\n\n";
                            $_c_ ='';
                            $_no--;
                            goto _start;
                        } else {
                            echo "[i] Berhasil kirim survey id-".$isi_survey->user_survey->survey_id." [Point saat ini: ".$isi_survey->user_points."]\n\n";
                        }
                    }  
                        
                    // save
                    $fh = fopen('new_accounts.CSV', "a");
                    fwrite($fh, $email.";".$pass.";".$dev_id."-".$dev_brand."-".$dev_model.";".$bearer."\n");
                    fclose($fh);  
                }

            } else {
                $login = $milieu->login($email, $pass, $dev_id, $dev_brand, $dev_model, $header);
                if($login == FALSE) { 
                    //
                } else {
                    $bearer = $login->token;

                    echo "[".$_no++."] Login sebagai id-".$login->user->id." [Points ".$login->user->points."]\n";
                    echo "[i] Device: ".ucwords($dev_brand)." - ".$dev_model." [".$dev_id."]\n";

                    $check_survey = $milieu->check_survey($bearer, $header);
                    if($check_survey == FALSE) {
                        echo "[!] Survey tidak tersedia saat ini\n\n";
                        if($login->user->points >= 11000) {
                            // save
                            $fh = fopen('ready_to_redeem.CSV', "a");
                            fwrite($fh, $email.";".$pass.";".$dev_id."-".$dev_brand."-".$dev_model.";;".$login->user->referral_code.";".$login->user->points." Point\n");
                            fclose($fh);
                        }
                    } else {
                        $check_antiBot = 1;
                        foreach ($survey_id as $survey_id_) {
                            if($survey_id_ == $check_survey['surveys'][0]['id']) {
                                $check_antiBot = 0;
                            }
                        }

                        $survey_id[] = $check_survey['surveys'][0]['id'];

                        if($check_antiBot == 1) {
                            $check_antiBot_questions[$check_survey['surveys'][0]['id']] = $milieu->check_antiBot_questions($check_survey);  
                        }

                        $survey = $milieu->response ($check_antiBot_questions, $check_survey);

                        $isi_survey = $milieu->send_response($survey, $bearer, $header);
                        if($isi_survey == FALSE) {
                            echo "[!] Gagal kirim survey\n";
                            $_r++;
                            if($_r >= 3) {
                                echo "[i] Hubungi Author\n\n";
                                die();
                            }
                            sleep(2);
                            echo "[i] Mencoba kembali...\n\n";
                            $_c_ ='';
                            $_no--;
                            goto _start;
                        } else {
                            echo "[i] Berhasil kirim survey id-".$isi_survey->user_survey->survey_id." [Point saat ini: ".$isi_survey->user_points."]\n\n";
                        }
                    }  
                    
                    // save
                    $fh = fopen('new_accounts.CSV', "a");
                    fwrite($fh, $email.";".$pass.";".$dev_id."-".$dev_brand."-".$dev_model.";".$bearer."\n");
                    fclose($fh);           
                }
            }  
        }  

        if(file_exists('new_accounts.CSV')) {
            unlink('accounts.CSV');
            sleep(1);
            rename('new_accounts.CSV', 'accounts.CSV');
        }
        break;
    
    case '3':
        echo "[i] Sebelumnya pilih `[2] Isi semua survey` untuk mengecek status point terbaru\n";
        echo "[?] Sudah [Y/N] ";
        $redeem_acc = trim(fgets(STDIN));
        if(strtolower($redeem_acc) == 'y' ) {
            if(file_exists('ready_to_redeem.CSV')) {
                $list = explode("\n",str_replace("\r","",file_get_contents("ready_to_redeem.CSV")));
                echo "[i] Ada ".(count($list)-1)." akun yang mencapai target point. Cek di ready_to_redeem.CSV\n\n";
                die();
            } else {
                echo "[!] Belum ada akun yang mencapai target point.\n\n";
                die();
            }
        } else {
            echo "\n";
            goto start;
        }
        break;

    default:
        goto start;
        break;
}
echo "\n";
?>
