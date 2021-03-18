<?php

/**
 * Settings Class Page
 * 
 * PHP version 5.6.x | 7.x | 8.x
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */

/**
 * Settings Class
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */
class WoocommerceIR_Settings_SMS
{
    private $_settings_api;

    /**
     * Class Constructor For Adding Actions
     *
     * @return void
     */
    function __construct()
    {
        $this->_settings_api = new WoocommerceIR_Settings_Fields_SMS;
        if (is_admin()) {
            add_action('admin_notices', array($this, 'adminNoticeAfterUpdate'));
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_init',  array ( 'WoocommerceIR_Bulk_SMS', 'sendSmsToBulkReceiver'), 11);
            add_action('admin_menu', array($this, 'adminMenu'), 60);
            add_action('admin_enqueue_scripts', array($this, 'scriptsCSSAdmin'));
            add_action(
                'ps_woo_sms_form_submit_sms_main_settings', function () {
                    submit_button(null, 'button-primary', 'submit_main', null);
                }
            );
            add_action(
                'ps_woo_sms_form_submit_sms_buyer_settings', function () {
                    submit_button(null, 'button-primary', 'submit_buyer', null);
                }
            );
            add_action(
                'ps_woo_sms_form_submit_sms_super_admin_settings', function () {
                    submit_button(null, 'button-primary', 'submit_super_admin', null);
                }
            );
            add_action(
                'ps_woo_sms_form_submit_sms_product_admin_settings', function () {
                    submit_button(null, 'button-primary', 'submit_product_admin', null);
                }
            );
            add_action(
                'ps_woo_sms_form_submit_sms_notif_settings', function () {
                    submit_button(null, 'button-primary', 'submit_notification', null);
                }
            );
            add_action('ps_woo_sms_form_bottom_persianwoosms_send', array ('WoocommerceIR_Bulk_SMS', 'sendSmsToBulk'));
            add_action('admin_footer', array ( 'WoocommerceIR_Bulk_SMS', 'bulkAdminFooterPsSms'), 10);
            add_filter('admin_footer_text', array($this , 'smsSettingPageFooter'));
            add_filter('update_footer', array($this , 'smsSettingPageFooterCopyright'), 11);
            add_action('load-edit.php', array ('WoocommerceIR_Bulk_SMS', 'bulkActionPsSms'));
            add_filter('sms_buyer_settings_settings', array($this, 'sms_buyer_settings_text'));
            add_filter('sms_super_admin_settings_settings', array($this, 'smsSuperAdminSettingsText'));
            add_filter('sms_product_admin_settings_settings', array($this, 'smsProductAdminSettingsText'));
        }

        if (class_exists('WoocommerceIR_Gateways_SMS'))
            new WoocommerceIR_Gateways_SMS();

        if (ps_sms_options('enable_admin_bar', 'sms_main_settings', 'off') == 'on' && is_admin())
            add_action('wp_before_admin_bar_render', array($this,'persianwooAdminbar'));

        if (ps_sms_options('enable_plugins', 'sms_main_settings', 'off') == 'off')
            return;

        if (ps_sms_options('enable_buyer', 'sms_buyer_settings', 'off') == 'on' 
            || ps_sms_options('enable_super_admin_sms', 'sms_super_admin_settings', 'off') == 'on' 
            || ps_sms_options('enable_product_admin_sms', 'sms_product_admin_settings', 'off') == 'on'
        ) {
            include_once PS_WOO_SMS_PLUGIN_LIB_PATH. '/class.order.php';
            if (class_exists('WoocommerceIR_Order_SMS'))
                new WoocommerceIR_Order_SMS();
        }

        if ((ps_sms_options('enable_metabox', 'sms_buyer_settings', 'off') == 'on' || ps_sms_options('enable_notif_sms_main', 'sms_notif_settings', 'off') == 'on') && is_admin()) {
            include_once PS_WOO_SMS_PLUGIN_LIB_PATH. '/class.metabox.php';
            if (class_exists('WoocommerceIR_Metabox_SMS'))
                new WoocommerceIR_Metabox_SMS();
        }

        if ((ps_sms_options('enable_product_admin_sms', 'sms_product_admin_settings', 'off') == 'on' || ps_sms_options('enable_notif_sms_main', 'sms_notif_settings', 'off') == 'on')  && is_admin()) {
            include_once PS_WOO_SMS_PLUGIN_LIB_PATH. '/class.products.tab.php';
            if (class_exists('WoocommerceIR_Tab_SMS'))
                new WoocommerceIR_Tab_SMS();
        }

        if (ps_sms_options('enable_notif_sms_main', 'sms_notif_settings', 'off') == 'on'
            || ps_sms_options('enable_super_admin_sms', 'sms_super_admin_settings', 'off') == 'on' 
            || ps_sms_options('enable_product_admin_sms', 'sms_product_admin_settings', 'off') == 'on' 
        ) {
            include_once PS_WOO_SMS_PLUGIN_LIB_PATH. '/class.notifications.php';
            if (class_exists('WoocommerceIR_Notification_SMS'))
                new WoocommerceIR_Notification_SMS();
        }

        if (ps_sms_options('enable_notif_sms_main', 'sms_notif_settings', 'off') == 'on') {
            include_once PS_WOO_SMS_PLUGIN_LIB_PATH. '/class.widget.php';
        }
    }

    /**
     * Initialize Function
     *
     * @return array
     */
    public static function init()
    {
        static $instance = false;
        return $instance = ( ! $instance ? new WoocommerceIR_Settings_SMS() : $instance );
    }

    /**
     * Admin CSS Scripts
     *
     * @return array
     */
    public function scriptsCSSAdmin()
    {
        global $post;
        if (is_object($post) && ( $post->post_type == 'shop_order' || $post->post_type == 'product')) {
            wp_enqueue_style('admin-persianwoosms-styles', PS_WOO_SMS_PLUGIN_PATH.'/assets/css/admin.css', false, date('Ymd'));
            wp_enqueue_script('admin-persianwoosms-scripts', PS_WOO_SMS_PLUGIN_PATH.'/assets/js/admin_script.js', array('jquery' ), false, true);
            wp_localize_script('admin-persianwoosms-scripts', 'persianwoosms', array('ajaxurl' => admin_url('admin-ajax.php')));
        }
        if (is_object($post)  && ($post->post_type == 'product') and (ps_sms_options('enable_plugins', 'sms_main_settings', 'off') != 'off')) {
            wp_register_script('repeatable-sms-tabs', PS_WOO_SMS_PLUGIN_PATH.'/assets/js/sms-product-tabs.js', array('jquery'), 'all');
            wp_enqueue_script('repeatable-sms-tabs');
            wp_register_style('repeatable-sms-tabs-styles', PS_WOO_SMS_PLUGIN_PATH.'/assets/css/sms-product-tabs.css', '', 'all');
            wp_enqueue_style('repeatable-sms-tabs-styles');
        }
    }

    /**
     * Admin Initialize Function
     *
     * @return array
     */
    function admin_init()
    {
        $this->_settings_api->set_sections($this->getSettingsSections());
        $this->_settings_api->set_fields($this->getSettingsFields());
        $this->_settings_api->admin_init();
    
        if (get_option('redirect_to_woo_sms_about_page_check') != 'yes') {
            ob_start();
            if (!headers_sent()) {
                wp_redirect(admin_url('index.php?page=about-WoocommercePluginSMSIR'));
            } else {
                update_option('redirect_to_woo_sms_about_page_check', 'yes');
                update_option('redirect_to_woo_sms_about_page', 'yes');
            }
        } else {
            update_option('redirect_to_woo_sms_about_page', 'yes');
        }
    }
    
    /**
     * Admin Notice After Update Function
     *
     * @return void
     */
    function adminNoticeAfterUpdate()
    {
        //after update
        $sms = true;
        //$sms = ps_sms_options( 'enable_sms', 'sms_main_settings', '' );
    }

    /**
     * Admin Menu
     *
     * @return void
     */
    function adminMenu()
    {    
        if (defined('PERSIAN_WOOCOMMERCE_VERSION') && version_compare(PERSIAN_WOOCOMMERCE_VERSION, '2.4.9', '>'))
            add_submenu_page('persian-wc', 'تنظیمات پیامک', 'تنظیمات پیامک', 'manage_woocommerce', 'WoocommercePluginSMSIR', array($this, 'settingPage'));
        else
            add_submenu_page('woocommerce', 'تنظیمات پیامک', 'تنظیمات پیامک', 'manage_woocommerce', 'WoocommercePluginSMSIR', array($this, 'settingPage'));

        if (get_option('redirect_to_woo_sms_about_page') != 'yes')
            add_submenu_page('index.php', 'درباره پیامک ووکامرس', 'پیامک ووکامرس', 'read', 'about-WoocommercePluginSMSIR', array($this, 'aboutPage'));
    }

    /**
     * About Page
     *
     * @return void
     */
    function aboutPage()
    {
        update_option('redirect_to_woo_sms_about_page_check', 'yes');
        include PS_WOO_SMS_PLUGIN_LIB_PATH. '/about.php';
    }

    /**
     * Setting Page
     *
     * @return void
     */
    function settingPage()
    {
        echo '<div class="wrap">';
            $this->_settings_api->show_navigation();
            $this->_settings_api->show_forms();
        echo '</div>';
    }

    /**
     * Woocommerce Admin Bar
     *
     * @return void
     */
    function persianwooAdminbar()
    {
        global $wp_admin_bar;
        $smsgateways = new WoocommerceIR_Gateways_SMS;
        if (current_user_can('manage_woocommerce') && is_admin_bar_showing()) {
            $wp_admin_bar->add_menu(
                array(
                    'id' => 'persianwoo_adminbar_send',
                    'title' => '<span class="ab-icon"></span>ارسال پیامک ووکامرس (اعتبار : '.$smsgateways->getCredit().' پیامک)',
                    'href' => admin_url('admin.php?page=WoocommercePluginSMSIR&send=true'),
                )
            );
        }
    }

    /**
     * Get Settings Sections
     *
     * @return void
     */
    function getSettingsSections()
    {
        $sections = array(
            array(
                'id' => 'sms_main_settings',
                'title' => 'همگانی'
            ),
            array(
                'id' => 'sms_buyer_settings',
                'title' => 'خریدار'
            ),
            array(
                'id' => 'sms_super_admin_settings',
                'title' => 'مدیر کل'
            ),
            array(
                'id' => 'sms_product_admin_settings',
                'title' => 'مدیر محصول'
            ),
            array(
                'id' => 'sms_notif_settings',
                'title' => 'اطلاع رسانی'
            ),
            array(
                'id' => 'persianwoosms_send',
                'title' => 'ارسال پیامک'
            )
        );
        return apply_filters('persianwoosms_settings_sections', $sections);
    }

    /**
     * Get Settings Fields
     *
     * @return void
     */    
    function getSettingsFields()
    {
        $smsgateways = new WoocommerceIR_Gateways_SMS;
        $settings_fields = array(
            'sms_main_settings' => apply_filters(
                'sms_main_settings_settings', 
                array(
                array(
                    'name' => 'enable_plugins',
                    'label' => 'فعال سازی کلی افزونه پیامک',
                    'desc' => 'در صورت فعالسازی این گزینه قابلیت ارسال پیامک به ووکامرس اضافه خواهد شد .',
                    'type' => 'checkbox',
                ),
                array(
                    'name' => 'enable_admin_bar',
                    'label' => 'لینک ارسال پیامک در ادمین بار',
                    'desc' => 'در صورت فعالسازی این گزینه لینک ارسال پیامک جهت دسترسی سریع تر به ادمین بار اضافه خواهد شد .',
                    'type' => 'checkbox',
                ),
                array(
                    'name' => 'header_1',
                    'label' => 'تنظیمات وب سرویس پیامک',
                    'desc' => '<hr/>',
                    'type' => 'html',
                ),
                array(
                    'name' => 'persian_woo_sms_apidomain',
                    'label' => 'لینک وب سرویس مثال: https://ws.sms.ir/',
                    'type' => 'text',
                ),                
                array(
                    'name' => 'persian_woo_sms_username',
                    'label' => '<a href="http://ip.sms.ir/#/UserApiKey" target="_blank">کلید وب سرویس پنل پیامک</a>',
                    'type' => 'text',
                ),
                array(
                    'name' => 'persian_woo_sms_password',
                    'label' => '<a href="http://ip.sms.ir/#/UserApiKey" target="_blank">کد امنیتی پنل پیامک</a>',
                    'type' => 'password',
                ),
                array(
                    'name' => 'persian_woo_sms_sender',
                    'label' => '<a href="http://ip.sms.ir/#/UserSetting" target="_blank">شماره ارسال کننده پیامک</a>',
                    'type' => 'text',
                ),                
                array(
                    'name' => 'persian_woo_sms_sender_clubnum',
                    'label' => '',
                    'desc' => 'در صورتیکه نیاز به ارسال پیامک از طریق خط باشگاه مشتریان تان دارید ، این گزینه را حتما انتخاب نمایید.',
                    'type' => 'checkbox',
                ),                
                array(
                    'name' => 'persian_woo_sms_Credit',
                    'label' => 'اعتبار پنل',
                    'desc' => '<p style="font-style: normal;">'.$smsgateways->getCredit().' پیامک</p>',
                    'type' => 'html',
                ),
                )
            ),
            'sms_buyer_settings' => apply_filters(
                'sms_buyer_settings_settings',  
                array(
                array(
                    'name' => 'enable_buyer',
                    'label' => 'ارسال پیام به خریدار',
                    'desc' => 'با انتخاب این گزینه ، در هنگام ثبت و یا تغییر وضعیت سفارش ، برای خریدار پیام ارسال می گردد .',
                    'type' => 'checkbox',
                ),
                array(
                    'name' => 'buyer_phone_label',
                    'label' => 'عنوان فیلد شماره موبایل',
                    'desc' => '<br/>این عنوان در صفحه تسویه حساب نمایش داده خواهد شد و جایگزین کلمه ی "تلفن" میگردد .',
                    'type' => 'text',
                    'default' => 'تلفن همراه',
                ),
                array(
                    'name' => 'force_enable_buyer',
                    'label' => 'اختیاری بودن دریافت پیام',
                    'desc' => 'فقط در صورت فعال سازی این قسمت ، گزینه "میخواهم از وضعیت سفارش از طریق پیامک آگاه شوم" در صفحه تسویه حساب نمایش داده خواهد شد و در غیر این صورت پیامک همواره ارسال خواهد شد .',
                    'type' => 'select',
                    'default' => 'yes',
                    'options' => array(
                        'yes' => 'خیر',
                        'no'   => 'بله' 
                    )
                ),
                array(
                    'name' => 'buyer_checkbox_text',
                    'label' => 'متن پذیرش دریافت پیام',
                    'desc' => '<br/>این متن بالای چک باکس انتخاب دریافت پیامک در صفحه تسویه حساب نمایش داده خواهد شد .',
                    'type' => 'textarea',
                    'default' => 'میخواهم از وضعیت سفارش از طریق اس ام اس آگاه شوم .'
                ),
                array(
                    'name' => 'enable_metabox',
                    'label' => 'متاباکس ارسال پیام',
                    'desc' => 'با انتخاب این گزینه ، در صفحه سفارشات متاباکس ارسال پیام به خریداران اضافه میشود .',
                    'type' => 'checkbox',
                ),
                array(
                    'name' => 'header_2',
                    'label' => 'وضعیت های دریافت پیام',
                    'desc' => '<hr/>',
                    'type' => 'html',
                ),
                array(
                    'name' => 'order_status',
                    'label' => 'وضعیت های سفارش مجاز',
                    'desc' => 'می توانید مشخص کنید خریدار در چه وضعیتی می توانند پیامک دریافت کنند.',
                    'type' => 'multicheck',
                    'options' => function_exists('get_all_woo_status_ps_sms') ? get_all_woo_status_ps_sms() : array(),
                ),
                array(
                    'name' => 'allow_buyer_select_status',
                    'label' => 'اجازه به انتخاب وضعیت ها توسط خریدار',
                    'desc' => 'با فعالسازی این گزینه ، خریدار میتواند در صفحه تسویه حساب وضعیت های دلخواه خود را از میان وضعیت های مجاز برای دریافت پیامک را انتخاب نماید . در صورت عدم فعالسازی این قسمت ، در تمام وضعیت های تیک خورده بالا پیامک ارسال میشود .',
                    'type' => 'select',
                    'default' => 'no',
                    'options' => array(
                        'yes' => 'بله',
                        'no'   => 'خیر'
                    )
                ),
                array(
                    'name' => 'buyer_status_mode',
                    'label' => 'روش انتخاب وضعیت ها',
                    'desc' => 'این قسمت ملزم به "بله" بودن تنظیمات "اجازه به انتخاب وضعیت ها توسط خریدار" است .',
                    'type' => 'select',
                    'default' => 'selector',
                    'options' => array(
                        'selector' => 'چند انتخابی',
                        'checkbox'   => 'چک باکس'
                    )
                ),
                array(
                    'name' => 'force_buyer_select_status',
                    'label' => 'الزامی بودن انتخاب حداقل یک وضعیت',
                    'desc' => 'با فعال سازی این گزینه ، کاربر می بایست حداقل یک وضعیت سفارش را از بین وضعیت های مجاز انتخاب کند . این قسمت نیز ملزم به "بله" بودن تنظیمات "انتخاب وضعیت ها توسط خریدار" است .',
                    'type' => 'select',
                    'default' => 'no',
                    'options' => array(
                        'yes' => 'بله',
                        'no'   => 'خیر'
                    )
                ),
                array(
                    'name' => 'buyer_select_status_text_top',
                    'label' => 'متن بالای انتخاب وضعیت ها',
                    'desc' => '<br/>این متن بالای لیست وضعیت ها در صفحه تسویه حساب برای انتخاب خریدار قرار میگیرد .',
                    'type' => 'textarea',
                    'default' => 'وضعیت هایی که مایل به دریافت پیامک هستید را انتخاب نمایید'
                ),
                array(
                    'name' => 'buyer_select_status_text_bellow',
                    'label' => 'متن پایین انتخاب وضعیت ها',
                    'desc' => '<br/>این متن پایین لیست وضعیت ها در صفحه تسویه حساب برای انتخاب خریدار قرار میگیرد .',
                    'type' => 'textarea',
                    'default' => ''
                ),
                array(
                    'name' => 'header_3',
                    'label' => 'متن پیام خریدار',
                    'desc' => '<hr/>',
                    'type' => 'html',
                ),
                )
            ),
            'sms_super_admin_settings' => apply_filters(
                'sms_super_admin_settings_settings',  
                array(
                array(
                    'name' => 'enable_super_admin_sms',
                    'label' => 'ارسال پیام به مدیران اصلی',
                    'desc' => 'با انتخاب این گزینه ، در هنگام ثبت و یا تغییر سفارش ، برای مدیران اصلی سایت پیامک ارسال می گردد .',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'name' => 'super_admin_phone',
                    'label' => 'شماره مدیران اصلی (پیامک)',
                    'desc' => '<br/>شماره ها را با کاما (,) جدا نمایید',
                    'type' => 'text'
                ),
                array(
                    'name' => 'super_admin_order_status',
                    'label' => 'وضعیت های دریافت پیام',
                    'desc' => '<br/>می توانید مشخص کنید مدیران اصلی سایت در چه وضعیت هایی پیامک دریافت کنند .',
                    'type' => 'multicheck',
                    'options' => function_exists('get_all_woo_status_ps_sms_for_super_admin') ? get_all_woo_status_ps_sms_for_super_admin() : array(),
                ),
                array(
                    'name' => 'header_super_admin',
                    'label' =>'متن پیام مدیر اصلی',
                    'type' => 'html',
                    'desc' => '<hr/>',
                ),
                )
            ),
            'sms_product_admin_settings' => apply_filters( 
                'sms_product_admin_settings_settings',  
                array(
                array(
                    'name' => 'enable_product_admin_sms',
                    'label' => 'ارسال پیام مدیران محصول',
                    'desc' => 'با انتخاب این گزینه ، در هنگام ثبت و یا تغییر سفارش ، برای مدیران هر محصول پیامک ارسال می گردد .',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'name' => 'header_product_admin',
                    'label' =>'متن پیام مدیران محصول',
                    'type' => 'html',
                    'desc' => '<hr/>',
                ),
                ) 
            ),
            'sms_notif_settings' => apply_filters( 
                'sms_notif_settings_settings',  
                array(
                array(
                    'name' => 'enable_notif_sms_main',
                    'label' => 'فعال سازی اطلاع رسانی',
                    'desc' => 'با فعالسازی این گزینه قابلیت اطلاع رسانی پیامکی محصولات به ووکامرس اضافه خواهد شد . و در صورت غیرفعالسازی کلیه قسمت های زیر بی تاثیر خواهند شد .',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'name' => 'notif_old_pr',
                    'label' => 'اعمال محصولات قدیمی',
                    'desc' => 'منظور از محصولات قدیمی محصولاتی هستند که قبل از نسخه جدید افزونه پیامک ایجاد شده اند و تنظیم نشده اند .',
                    'type' => 'select',
                    'default' => 'no',
                    'options' => array(
                        'yes' => 'اعمال تنظیمات پیشفرض بر روی محصولات قدیمی',
                        'no'   => 'اطلاع رسانی پیامکی رو برای محصولات قدیمی نادیده بگیر'
                    )
                ),
                array(
                    'name' => 'header_1',
                    'label' => 'تذکر',
                    'desc' => 'کلیه قسمت های زیر تنظیمات پیشفرض بوده و برای هر محصول قابل تنظیم جدا گانه می باشد .<br/><br/>منظور از اطلاع رسانی محصولات ، آگاه سازی کاربران از وضعیت های هر محصول دلخواه شان نظیر ، فروش حراج ، اتمام محصول . ... می باشد . ',
                    'type' => 'html',
                ),
                array(
                    'name' => 'header_2',
                    'label' => 'نمایش در صفحه محصول',
                    'desc' => '<hr/>',
                    'type' => 'html',
                ),
                array(
                    'name' => 'enable_notif_sms',
                    'label' => 'نمایش خودکار',
                    'desc' => 'با فعالسازی این قسمت گزینه "میخواهم از وضعیت محصول توسط پیامک با خبر شوم" در صفحه محصولات اضافه خواهد شد .<br/>
						میتوانید این قسمت "نمایش خودکار" را غیرفعال نمایید و بجای آن از شورت کد [woo_ps_sms] یا ابزارک "اطلاع رسانی پیامکی ووکامرس" در صفحه محصول استفاده نمایید .<br/><br/>
						تذکر : برای جلوگیری از مشکل تداخل  جیکوئری ، در صفحه هر محصول فقط از یکی از حالت های "نمایش خودکار" ، "ابزارک" یا "شورت کد" استفاده نمایید .',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'name' => 'notif_title',
                    'label' => 'متن سر تیتر گزینه ها',
                    'desc' => '<br/>این متن در صفحه محصول به صورت چک باکس ظاهر خواهد شد و کاربر با فعال کردن آن میتواند شماره خود را برای دریافت اطلاعیه آن محصول وارد نماید .',
                    'type' => 'text',
                    'default' => "به من از طریق پیامک اطلاع بده"
                ),
                array(
                    'name' => 'header_3',
                    'label' => 'گزینه های اصلی',
                    'desc' => '<hr/>',
                    'type' => 'html',
                ),
                array(
                    'name' => 'header_4',
                    'label' => 'شورت کد های قابل استفاده',
                    'desc' => "شورت کد های قابل استفاده در متن پیامک ها :<br/><br/><code>{product_id}</code> : آیدی محصول ، <code>{sku}</code> : شناسه محصول ، <code>{product_title}</code> : عنوان محصول ، <code>{regular_price}</code> قیمت اصلی ، <code>{onsale_price}</code> : قیمت فروش فوق العاده<br/><code>{onsale_from}</code> : تاریخ شروع فروش فوق العاده ، <code>{onsale_to}</code> : تاریخ اتمام فروش فوق العاده ، <code>{stock}</code> : موجودی انبار<hr/>",
                    'type' => 'html',
                ),
                array(
                    'name' => 'enable_onsale',
                    'label' => 'زمانیکه محصول حراج شد',
                    'desc' => 'هنگامی که این گزینه فعال باشد در صورت حراج نبودن محصول گزینه "زمانیکه محصول حراج شد" نیز به لیست گزینه های اطلاع رسانی اضافه خواهد شد .',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'name' => 'notif_onsale_text',
                    'label' => 'متن گزینه "زمانیکه محصول حراج شد"',
                    'desc' => '<br/>میتوانید متن دلخواه خود را جایگزین جمله "زمانیکه محصول حراج شد" نمایید .',
                    'type' => 'text',
                    'default' => "زمانیکه محصول حراج شد"
                ),
                array(
                    'name' => 'notif_onsale_sms',
                    'label' =>'متن پیامک "زمانیکه محصول حراج شد"',
                    'desc' => '<hr/>',
                    'type' => 'textarea',
                    'default' => "سلام\nمحصول {product_title} از قیمت {regular_price} به قیمت {onsale_price} کاهش یافت ."
                ),
                array(
                    'name' => 'enable_notif_no_stock',
                    'label' => 'زمانیکه محصول موجود شد',
                    'desc' => 'هنگامی که این گزینه فعال باشد در صورت ناموجود شدن محصول گزینه "زمانیکه محصول موجود شد" نیز به لیست گزینه های اطلاع رسانی اضافه خواهد شد .',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'name' => 'notif_no_stock_text',
                    'label' => 'متن گزینه "زمانیکه محصول موجود شد"',
                    'desc' => '<br/>میتوانید متن دلخواه خود را جایگزین جمله "زمانیکه محصول موجود شد" نمایید .',
                    'type' => 'text',
                    'default' => "زمانیکه محصول موجود شد"
                ),
                array(
                    'name' => 'notif_no_stock_sms',
                    'label' =>'متن پیامک "زمانیکه محصول موجود شد"',
                    'desc' => '<hr/>',
                    'type' => 'textarea',
                    'default' => "سلام\nمحصول {product_title} هم اکنون موجود و قابل خرید می باشد ."
                ),
                array(
                    'name' => 'enable_notif_low_stock',
                    'label' => 'زمانیکه موجودی انبار محصول کم شد',
                    'desc' => 'هنگامی که این گزینه فعال باشد ، گزینه "زمانیکه موجودی انبار محصول کم شد" نیز به لیست گزینه های اطلاع رسانی اضافه خواهد شد .',
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                array(
                    'name' => 'notif_low_stock_text',
                    'label' => 'متن گزینه "زمانیکه موجودی انبار محصول کم شد"',
                    'desc' => '<br/>میتوانید متن دلخواه خود را جایگزین جمله "زمانیکه موجودی انبار محصول کم شد" نمایید .',
                    'type' => 'text',
                    'default' => "زمانیکه موجودی انبار محصول کم شد"
                ),
                array(
                    'name' => 'notif_low_stock_sms',
                    'label' =>'متن پیامک "زمانیکه محصول موجودی انبار کم شد"',
                    'desc' => '',
                    'type' => 'textarea',
                    'default' => "سلام\nموجودی محصول {product_title} کم می باشد . لطفا در صورت تمایل به خرید سریعتر اقدام نمایید ."
                ),
                array(
                    'name' => 'header_5',
                    'label' => 'تذکر',
                    'desc' => 'توجه داشته باشید که عملکرد گزینه های مربوط به "موجودی و انبار" وابسته به <a href="'.admin_url('admin.php?page=wc-settings&tab=products&section=inventory').'" target="_blank">تنظیمات ووکامرس</a> خواهد بود .',
                    'type' => 'html',
                ),
                array(
                    'name' => 'header_6',
                    'label' => 'گزینه های اضافی',
                    'desc' => '<hr/>',
                    'type' => 'html',
                ),
                array(
                    'name' => 'notif_options',
                    'label' =>'گزینه های دلخواه',
                    'desc' => 'شما میتوانید گزینه های دلخواه خود را برای نمایش در صفحه محصولات ایجاد نمایید و به صورت دستی به خریدارانی که در گزینه های بالا عضو شده اند پیامک ارسال کنید .<br/>
						برای اضافه کردن گزینه ها ، همانند نمونه بالا ابتدا یک کد عددی دلخواه تعریف کنید سپس بعد از قرار دادن عبارت ":" متن مورد نظر را بنویسید .<br/>
						دقت کنید که کد عددی هر گزینه بسیار مهم بوده و از تغییر کد مربوط به هر گزینه بعد از ذخیره تنظیمات خود داری نمایید .',
                    'type' => 'textarea',
                    'default' => "1:زمانیکه محصول توقف فروش شد\n2:زمانیکه نسخه جدید محصول منتشر شد\n"
                ),
                array(
                    'name' => 'header_7',
                    'label' => 'تذکر',
                    'desc' => 'متن پیامک مربوط به گزینه های اضافی را می توانید در صفحه هر محصول در باکس سمت چپ آن نوشته و پیامک را ارسال نمایید .',
                    'type' => 'html',
                ),
                ) 
            ),
        );
        return apply_filters('persianwoosms_settings_section_content', $settings_fields);
    }

    
    /**
     * Sms Buyer Settings Text
     *
     * @param array $settings settings
     *
     * @return array Settings Data
     */
    function sms_buyer_settings_text($settings)
    {
        $statuses = function_exists('get_all_woo_status_ps_sms') ? get_all_woo_status_ps_sms() : array();
        foreach ((array) $statuses as $status_val => $status_name) {
            $text = array( 
                array(
                    'name' => 'sms_body_' . $status_val,
                    'label' =>'وضعیت ' . $status_name,
                    'desc' => "همچنین میتوانید از شورت کد های معرفی شده در انتهای این بخش استفاده نمایید .<hr/>",
                    'type' => 'textarea',
                    'default' => "سلام {b_first_name} {b_last_name}\nسفارش {order_id} دریافت شد و هم اکنون در وضعیت "  . $status_name . " می باشد\nآیتم های سفارش : {all_items}\nمبلغ سفارش : {price}\nشماره تراکنش : {transaction_id}"
                ),
            );
            $settings = array_merge($settings, $text);
        }

        $text = array( 
            array(
                'name' => 'sms_body_shortcodes',
                'label' =>'شورت کد های پیام',
                'type' => 'html',
                'desc' => function_exists('sms_text_order_shortcode') ? sms_text_order_shortcode() : '',
            ),
        );
        $settings = array_merge($settings, $text);
        return $settings;
    }

    /**
     * Sms Super Admin Settings Text
     *
     * @param array $settings settings
     *
     * @return array Settings Data
     */
    function smsSuperAdminSettingsText($settings)
    {
        $statuses = function_exists('get_all_woo_status_ps_sms') ? get_all_woo_status_ps_sms() : array();
        foreach ((array) $statuses as $status_val => $status_name) {
            $text = array(
                array(
                    'name' => 'super_admin_sms_body_' . $status_val,
                    'label' =>'وضعیت ' . $status_name,
                    'desc' => "همچنین میتوانید از شورت کد های معرفی شده در انتهای این بخش استفاده نمایید .<hr/>",
                    'type' => 'textarea',
                    'default' => "سلام مدیر\nسفارش {order_id} ثبت شده است و هم اکنون در وضعیت "  . $status_name . " می باشد\nآیتم های سفارش : {all_items}\nمبلغ سفارش : {price}"
                )
            );
            $settings = array_merge($settings, $text);
        }

        $text = array(
            array(
                'name' => 'sms_body_shortcodes_super_admin',
                'label' =>'شورت کد های پیام',
                'type' => 'html',
                'desc' => function_exists('sms_text_order_shortcode') ? sms_text_order_shortcode() : '',
            ),
            array(
                'name' => 'header_3',
                'label' => 'متن پیامک های موجودی انبار',
                'desc' => '<hr/>',
                'type' => 'html',
            ),
            array(
                'name' => 'header_5',
                'label' => 'تذکر',
                'desc' => 'توجه داشته باشید که عملکرد گزینه های مربوط به "موجودی و انبار" برای "مدیران محصول" نیز اعمال خواهد شد و وابسته به <a href="'.admin_url('admin.php?page=wc-settings&tab=products&section=inventory').'" target="_blank">تنظیمات ووکامرس</a> خواهد بود .',
                'type' => 'html',
            ),
            array(
                'name' => 'admin_out_stock',
                'label' => 'اتمام موجودی انبار',
                'desc' => "متن پیامک زمانیکه موجودی انبار تمام شد",
                'type' => 'textarea',
                'default' => "سلام\nموجودی انبار محصول {product_title} به اتمام رسیده است ."
            ),
            array(
                'name' => 'admin_low_stock',
                'label' => 'کاهش موجودی انبار',
                'desc' => "متن پیامک زمانیکه موجودی انبار کم است",
                'type' => 'textarea',
                'default' => "سلام\nموجودی انبار محصول {product_title} رو به اتمام است ."
            ),
            array(
                'name' => 'header_4',
                'label' => 'شورت کد های قابل استفاده',
                'desc' => "شورت کد های قابل استفاده در متن پیامک های مرتبط با موجوی انبار :<br/><br/><code>{product_id}</code> : آیدی محصول ، <code>{sku}</code> : شناسه محصول ، <code>{product_title}</code> : عنوان محصول ، <code>{stock}</code> : موجودی انبار",
                'type' => 'html',
            ),
        );
        $settings = array_merge($settings, $text);
        return $settings;
    }

    /**
     * Sms Product Admin Settings Text
     *
     * @param array $settings settings
     *
     * @return array Settings Data
     */
    function smsProductAdminSettingsText($settings)
    {
        $statuses = function_exists('get_all_woo_status_ps_sms') ? get_all_woo_status_ps_sms() : array();
        foreach ((array) $statuses as $status_val => $status_name) {
            $text = array(
                array(
                    'name' => 'product_admin_sms_body_' . $status_val,
                    'label' => 'وضعیت ' . $status_name,
                    'desc' => "همچنین علاوه بر شورت کد های معرفی شده در انتهای این بخش می توانید از کد میانبر زیر نیز استفاده نمایید :<br/><code>{vendor_items}</code> : آیتم های سفارش اختصاص یافته به هر شماره <hr/>",
                    'type' => 'textarea',
                    'default' => "سلام\nسفارش {order_id} ثبت شده است و هم اکنون در وضعیت "  . $status_name . " می باشد\nآیتم های سفارش اختصاص یافته به شماره شما : {vendor_items}",
                ),
            );
            $settings = array_merge($settings, $text);
        }

        $text = array(
            array(
                'name' => 'sms_body_shortcodes_product_admin',
                'label' =>'شورت کد های پیام',
                'type' => 'html',
                'desc' => function_exists('sms_text_order_shortcode') ? sms_text_order_shortcode() : '',
            )
        );
        $settings = array_merge($settings, $text);
        return $settings;
    }

    /**
     * Sms Setting Page Footer Text
     *
     * @param array $text footer text
     *
     * @return string footer text
     */
    function smsSettingPageFooter($text)
    {
        if (isset($_GET['page']) && $_GET['page'] == 'WoocommercePluginSMSIR') 
            return ' این افزونه به صورت رایگان از سوی pejmankheyri@gmail.com ارائه شده است .';
        return $text;
    }

    /**
     * Sms Setting Page Footer Copyright
     *
     * @param array $text footer copyright
     *
     * @return string footer copyright
     */
    function smsSettingPageFooterCopyright($text)
    {
        if (isset($_GET['page']) && $_GET['page'] == 'WoocommercePluginSMSIR') 
            $text = 'پیامک ووکامرس نگارش ' . PS_WOO_SMS_VERSION;
        return $text;
    }
}