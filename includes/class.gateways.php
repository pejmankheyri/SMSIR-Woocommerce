<?php

/**
 * SMSIR Gateway Class Page
 * 
 * PHP version 5.6.x | 7.x | 8.x
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */

/**
 * SmsIr Bulk Gateway Class
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */
class WoocommerceIR_Gateways_SMS
{
    
    private static $_instance;

    /**
     * Gets API Customer Club Send To Categories Url.
     *
     * @return string Indicates the Url
     */
    protected function getAPICustomerClubSendToCategoriesUrl()
    {
        return "api/CustomerClub/SendToCategories";
    }

    /**
     * Gets API Message Send Url.
     *
     * @return string Indicates the Url
     */
    protected function getAPIMessageSendUrl()
    {
        return "api/MessageSend";
    }

    /**
     * Gets API Customer Club Add Contact And Send Url.
     *
     * @return string Indicates the Url
     */
    protected function getAPICustomerClubAddAndSendUrl()
    {
        return "api/CustomerClub/AddContactAndSend";
    }

    /**
     * Gets API credit Url.
     *
     * @return string Indicates the Url
     */
    protected function getAPIcreditUrl()
    {
        return "api/credit";
    }

    /**
     * Gets API Verification Code Url.
     *
     * @return string Indicates the Url
     */
    protected function getAPIVerificationCodeUrl()
    {
        return "api/VerificationCode";
    }

    /**
     * Gets Api Token Url.
     *
     * @return string Indicates the Url
     */
    protected function getApiTokenUrl()
    {
        return "api/Token";
    }

    /**
     * Gateways init function
     *
     * @return array Indicates the instance
     */
    public static function init()
    {
        if (!self::$_instance)
            self::$_instance = new WoocommerceIR_Gateways_SMS();
        return self::$_instance;
    }

    /**
     * Send SMS.
     *
     * @param sms_data[] $sms_data array structure of sms_data
     *
     * @return boolean
     */
    public function sendSMSir($sms_data)
    {
        $response = false;
        $username = ps_sms_options('persian_woo_sms_username', 'sms_main_settings');
        $password = ps_sms_options('persian_woo_sms_password', 'sms_main_settings');
        
        if (empty($username) || empty($password)) {
            return $response;
        }

        $clubnum_check = ps_sms_options('persian_woo_sms_sender_clubnum', 'sms_main_settings');
        $message = $sms_data['sms_body'];
        $numbers1 = $sms_data['number'];

        if ((strpos($message, '&#xfdfc;') != false) || (strpos($message, '&#x0627;') != false)) {
            $message = str_replace("&#xfdfc;", "ریال", $message);
            $message = str_replace("&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;", "تومان", $message);
            if (strpos($message, '&#x0631;') != false) {
                $message = str_replace("&#x0631;&#xFBFE;&#x0627;&#x0644;", "ریال", $message);
            }
        }

        if ($numbers1) {
            foreach ($numbers1 as $keys => $values) {
                if (($this->isMobile($values)) || ($this->isMobileWithz($values))) {
                    $number[] = doubleval($values);
                }
            }

            @$numbers = array_unique($number);

            if (is_array($numbers) && $numbers) {
                foreach ($numbers as $key => $value) {
                    $Messages[] = $message;
                }
            }

            date_default_timezone_set('Asia/Tehran');

            $SendDateTime = date("Y-m-d")."T".date("H:i:s");

            if ($clubnum_check == "on") {
                foreach ($numbers as $num_keys => $num_vals) {
                    $contacts[] = array(
                        "Prefix" => "",
                        "FirstName" => "" ,
                        "LastName" => "",
                        "Mobile" => $num_vals,
                        "BirthDay" => "",
                        "CategoryId" => "",
                        "MessageText" => $message
                    );
                }

                $CustomerClubInsertAndSendMessage = $this->customerClubInsertAndSendMessage($contacts);

                if ($CustomerClubInsertAndSendMessage == true) {
                    $response = true;
                } else {
                    $response = false;
                }
            } else {
                $SendMessage = $this->sendMessage($numbers, $Messages, $SendDateTime);
                if ($SendMessage == true) {
                    $response = true;
                } else {
                    $response = false;
                }
            }
        } else {
            $response = false;
        }
        return $response;
    }

    /**
     * Verification Code.
     *
     * @param string $Code         Code
     * @param string $MobileNumber Mobile Number
     * 
     * @return string Indicates the sent sms result
     */
    public function sendSMSforVerification($Code, $MobileNumber)
    {
        $username = ps_sms_options('persian_woo_sms_username', 'sms_main_settings');
        $password = ps_sms_options('persian_woo_sms_password', 'sms_main_settings');
        $apidomain = ps_sms_options('persian_woo_sms_apidomain', 'sms_main_settings');

        $token = $this->_getToken($username, $password);
        if ($token != false) {
            $postData = array(
                'Code' => $Code,
                'MobileNumber' => $MobileNumber,
            );

            $url = $apidomain.$this->getAPIVerificationCodeUrl();
            $VerificationCode = $this->_execute($postData, $url, $token);
            $object = json_decode($VerificationCode);

            $result = false;
            if (is_object($object)) {
                if ($object->IsSuccessful == true) {
                    $result = true;
                } else {
                    $result = false;
                }
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Get Credit.
     *
     * @return string Indicates the sent sms result
     */
    public function getCredit()
    {
        $username = ps_sms_options('persian_woo_sms_username', 'sms_main_settings');
        $password = ps_sms_options('persian_woo_sms_password', 'sms_main_settings');
        $apidomain = ps_sms_options('persian_woo_sms_apidomain', 'sms_main_settings');

        $token = $this->_getToken($username, $password);
        if ($token != false) {

            $url = $apidomain.$this->getAPIcreditUrl();
            $GetCredit = $this->_executeCredit($url, $token);

            $object = json_decode($GetCredit);

            $result = false;
            if (is_object($object)) {
                if ($object->IsSuccessful == true) {
                    $result = $object->Credit;
                } else {
                    $result = $object->Message;
                }
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Customer Club Send To Categories.
     *
     * @param Messages[] $Messages array structure of messages
     * 
     * @return string Indicates the sent sms result
     */
    public function sendSMStoCustomerclubContacts($Messages)
    {
        $username = ps_sms_options('persian_woo_sms_username', 'sms_main_settings');
        $password = ps_sms_options('persian_woo_sms_password', 'sms_main_settings');
        $apidomain = ps_sms_options('persian_woo_sms_apidomain', 'sms_main_settings');

        $contactsCustomerClubCategoryIds = array();
        $token = $this->_getToken();

        if ($token != false) {
            $postData = array(
                'Messages' => $Messages['sms_body'],
                'contactsCustomerClubCategoryIds' => $contactsCustomerClubCategoryIds,
                'SendDateTime' => '',
                'CanContinueInCaseOfError' => 'false'
            );

            $url = $apidomain.$this->getAPICustomerClubSendToCategoriesUrl();
            $CustomerClubSendToCategories = $this->_execute($postData, $url, $token);
            $object = json_decode($CustomerClubSendToCategories);

            if (is_object($object)) {
                if ($object->IsSuccessful == true) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Send sms.
     *
     * @param MobileNumbers[] $MobileNumbers array structure of mobile numbers
     * @param Messages[]      $Messages      array structure of messages
     * @param string          $SendDateTime  Send Date Time
     *
     * @return string Indicates the sent sms result
     */
    public function sendMessage($MobileNumbers, $Messages, $SendDateTime = '')
    {
        $username = ps_sms_options('persian_woo_sms_username', 'sms_main_settings');
        $password = ps_sms_options('persian_woo_sms_password', 'sms_main_settings');
        $from = ps_sms_options('persian_woo_sms_sender', 'sms_main_settings');
        $apidomain = ps_sms_options('persian_woo_sms_apidomain', 'sms_main_settings');

        $token = $this->_getToken($username, $password);

        if ($token != false) {
            $postData = array(
                'Messages' => $Messages,
                'MobileNumbers' => $MobileNumbers,
                'LineNumber' => $from,
                'SendDateTime' => $SendDateTime,
                'CanContinueInCaseOfError' => 'false'
            );

            $url = $apidomain.$this->getAPIMessageSendUrl();
            $SendMessage = $this->_execute($postData, $url, $token);
            $object = json_decode($SendMessage);
 
            $result = false;
            if (is_object($object)) {
                if ($object->IsSuccessful == true) {
                    $result = true;
                } else {
                    $result = false;
                }
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Customer Club Insert And Send Message.
     *
     * @param data[] $data array structure of contacts data
     * 
     * @return string Indicates the sent sms result
     */
    public function customerClubInsertAndSendMessage($data)
    {
        $username = ps_sms_options('persian_woo_sms_username', 'sms_main_settings');
        $password = ps_sms_options('persian_woo_sms_password', 'sms_main_settings');
        $apidomain = ps_sms_options('persian_woo_sms_apidomain', 'sms_main_settings');

        $token = $this->_getToken($username, $password);
        if ($token != false) {
            $postData = $data;

            $url = $apidomain.$this->getAPICustomerClubAddAndSendUrl();
            $CustomerClubInsertAndSendMessage = $this->_execute($postData, $url, $token);
            $object = json_decode($CustomerClubInsertAndSendMessage);

            $result = false;
            if (is_object($object)) {
                if ($object->IsSuccessful == true) {
                    $result = true;
                } else {
                    $result = false;
                }
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Gets token key for all web service requests.
     *
     * @return string Indicates the token key
     */
    private function _getToken()
    {
        $username = ps_sms_options('persian_woo_sms_username', 'sms_main_settings');
        $password = ps_sms_options('persian_woo_sms_password', 'sms_main_settings');
        $apidomain = ps_sms_options('persian_woo_sms_apidomain', 'sms_main_settings');

        $postData = array(
            'UserApiKey' => $username,
            'SecretKey' => $password,
            'System' => 'woocommerce_3_v_3_1'
        );
        $postString = json_encode($postData);

        $ch = curl_init($apidomain.$this->getApiTokenUrl());
        curl_setopt(
            $ch, 
            CURLOPT_HTTPHEADER, 
            array(
                'Content-Type: application/json'
            )
        );
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);

        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result);

        $resp = false;
        if (is_object($response)) {
            @$IsSuccessful = $response->IsSuccessful;
            if ($IsSuccessful == true) {
                @$TokenKey = $response->TokenKey;
                $resp = $TokenKey;
            } else {
                $resp = false;
            }
        }
        return $resp;
    }

    /**
     * Executes the main method.
     *
     * @param postData[] $postData array of json data
     * @param string     $url      url
     * @param string     $token    token string
     * 
     * @return string Indicates the curl execute result
     */
    private function _execute($postData, $url, $token)
    {
        $postString = json_encode($postData);

        $ch = curl_init($url);
        curl_setopt(
            $ch, 
            CURLOPT_HTTPHEADER, 
            array(
                'Content-Type: application/json',
                'x-sms-ir-secure-token: '.$token
            )
        );
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Executes the main method.
     *
     * @param string $url   url
     * @param string $token token string
     * 
     * @return string Indicates the curl execute result
     */
    private function _executeCredit($url, $token)
    {
        $ch = curl_init($url);
        curl_setopt(
            $ch, 
            CURLOPT_HTTPHEADER, 
            array(
                'Content-Type: application/json',
                'x-sms-ir-secure-token: '.$token
            )
        );
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Check if mobile number is valid.
     *
     * @param string $mobile mobile number
     * 
     * @return boolean Indicates the mobile validation
     */
    public function isMobile($mobile)
    {
        if (preg_match('/^09(0[1-5]|1[0-9]|3[0-9]|2[0-2]|9[0-1])-?[0-9]{3}-?[0-9]{4}$/', $mobile)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if mobile with zero number is valid.
     *
     * @param string $mobile mobile with zero number
     * 
     * @return boolean Indicates the mobile with zero validation
     */
    public function isMobileWithz($mobile)
    {
        if (preg_match('/^9(0[1-5]|1[0-9]|3[0-9]|2[0-2]|9[0-1])-?[0-9]{3}-?[0-9]{4}$/', $mobile)) {
            return true;
        } else {
            return false;
        }
    }
}