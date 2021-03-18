<?php

/**
 * Order Class Page
 * 
 * PHP version 5.6.x | 7.x | 8.x
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */

/**
 * Order Class
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */
class WoocommerceIR_Order_SMS
{
    /**
     * Class Constructor For Adding Actions
     *
     * @return void
     */
    public function __construct()
    {
        add_action('woocommerce_after_order_notes', array($this, 'addSmsFieldInCheckout'));
        add_action('woocommerce_checkout_process', array($this, 'addSmsFieldInCheckoutProcess'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'saveSmsFieldInOrderMeta'));
        add_action('woocommerce_order_status_changed', array($this, 'sendSmsWhenOrderStatusChanged'), 10, 3);
        add_filter('woocommerce_checkout_fields', array($this, 'changeCheckoutPhoneLabel'), 0);
        add_filter('woocommerce_form_field_persian_woo_sms_multiselect', 'add_multi_select_checkbox_to_checkout_ps_sms', 11, 4);
        add_filter('woocommerce_form_field_persian_woo_sms_multicheckbox', 'add_multi_select_checkbox_to_checkout_ps_sms', 11, 4);

        if (is_admin()) {
            add_action('woocommerce_admin_order_data_after_order_details', array($this, 'adminOrderDataAfterOrderDetails'));
            add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'showSmsFieldInAdminOrderMeta'), 10, 1);
            add_action('wp_ajax_change_sms_text', array($this, 'changeSmsText'));
            add_action('wp_ajax_nopriv_change_sms_text', array($this, 'changeSmsText'));
        }
        add_action('wp_enqueue_scripts', array($this, 'scriptsCSSFrontend'));
    }

    /**
     * Frontend CSS Scripts
     *
     * @return void
     */
    public function scriptsCSSFrontend()
    {
        if (ps_sms_options('allow_buyer_select_status', 'sms_buyer_settings', 'no') == 'yes') {
            wp_register_script('persian-woo-sms-frontend', PS_WOO_SMS_PLUGIN_PATH.'/assets/js/status_selector_front_end.js', array('jquery'), PS_WOO_SMS_VERSION, true);
            wp_localize_script(
                'persian-woo-sms-frontend', 
                'persian_woo_sms', 
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'chosen_placeholder_single' => __('گزینه مورد نظر را انتخاب نمایید', 'persianwoosms'),
                    'chosen_placeholder_multi' => __('گزینه های مورد نظر را انتخاب نمایید', 'persianwoosms'),
                    'chosen_no_results_text' => __('هیچ گزینه ای وجود ندارد .', 'persianwoosms'),
                )
            );
            wp_enqueue_script('persian-woo-sms-frontend');

            if (ps_sms_options('force_enable_buyer', 'sms_buyer_settings', 'no') != 'yes' &&  ps_sms_options('allow_buyer_select_status', 'sms_buyer_settings', 'no') == 'yes') {
                wc_enqueue_js( 
                    "jQuery( '#buyer_sms_status_field' ).hide();
					jQuery( 'input[name=buyer_sms_notify]' ).change( function () {
						if ( jQuery( this ).is( ':checked' ) )
							jQuery( '#buyer_sms_status_field' ).show();
						else
							jQuery( '#buyer_sms_status_field' ).hide();
					} ).change();"
                );
            }
        }
    }

    /**
     * Change Checkout Phone Label
     *
     * @param string $fields fields
     *
     * @return array
     */
    function changeCheckoutPhoneLabel($fields)
    {
        $fields['billing']['billing_phone']['label'] = ps_sms_options('buyer_phone_label', 'sms_buyer_settings', '') ? ps_sms_options('buyer_phone_label', 'sms_buyer_settings', 'تلفن همراه') : $fields['billing']['billing_phone']['label'];
        return $fields;
    }

    /**
     * Add SMS Field In Checkout Page
     *
     * @param string $checkout checkout
     *
     * @return void
     */
    function addSmsFieldInCheckout($checkout)
    {
        if (ps_sms_options('enable_buyer', 'sms_buyer_settings', 'off') == 'off' || count((array) get_allowed_woo_status_ps_sms()) < 0)
            return;
        echo '<div id="addSmsFieldInCheckout">';
        $checkbox_text = ps_sms_options('buyer_checkbox_text', 'sms_buyer_settings', 'مرا با ارسال پیامک از وضعیت سفارش آگاه کن');
        $required = (ps_sms_options('force_enable_buyer', 'sms_buyer_settings', 'no') == 'yes') ? true : false;
        if (!$required) {
            woocommerce_form_field( 
                'buyer_sms_notify', 
                array(
                    'type' => 'checkbox',
                    'class' => array('buyer-sms-notify form-row-wide'),
                    'label' => __($checkbox_text, 'persianwoosms') ? __($checkbox_text, 'persianwoosms') : '',
                    'label_class' => '',
                    'required' => $required,
                ), 
                $checkout->get_value('buyer_sms_notify')
            );
        }

        if (ps_sms_options('allow_buyer_select_status', 'sms_buyer_settings', 'no') == 'yes') {
            $multiselect_text = ps_sms_options('buyer_select_status_text_top', 'sms_buyer_settings', '');
            $multiselect_text_bellow = ps_sms_options('buyer_select_status_text_bellow', 'sms_buyer_settings', '');
            $required = (ps_sms_options('force_buyer_select_status', 'sms_buyer_settings', 'no') == 'yes') ? true : false;
            $mode = (ps_sms_options('buyer_status_mode', 'sms_buyer_settings', 'selector') == 'selector') ? 'persian_woo_sms_multiselect' : 'persian_woo_sms_multicheckbox';
            woocommerce_form_field( 
                'buyer_sms_status', 
                array(
                    'type' => $mode ? $mode : '',
                    'class' => array('buyer-sms-status form-row-wide wc-enhanced-select'),
                    'label' => $multiselect_text ? $multiselect_text : '',
                    'options' => get_allowed_woo_status_ps_sms(),
                    'required' => $required,
                    'description' =>  $multiselect_text_bellow ? ($multiselect_text_bellow) : '',
                ), 
                $checkout->get_value('buyer_sms_status')
            );
        }
        echo '</div>';
    }

    /**
     * Adds SMS Field In Checkout Process
     *
     * @return void
     */
    function addSmsFieldInCheckoutProcess()
    {
        if (!empty($_POST['billing_phone'])) 
            $_POST['billing_phone'] = fa_en_mobile_woo_sms($_POST['billing_phone']);

        if(ps_sms_options('enable_buyer', 'sms_buyer_settings', 'off') == 'off' || count((array) get_allowed_woo_status_ps_sms()) < 0)
            return;

        if (ps_sms_options('force_enable_buyer', 'sms_buyer_settings', 'no') != 'yes' && ! empty($_POST['buyer_sms_notify']) && empty($_POST['billing_phone'])) {
            wc_add_notice(__('برای دریافت پیامک می بایست فیلد شماره تلفن را پر نمایید .'), 'error');
        }

        if ((ps_sms_options('force_enable_buyer', 'sms_buyer_settings', 'no') == 'yes' || (ps_sms_options('force_enable_buyer', 'sms_buyer_settings', 'no') != 'yes' && !empty($_POST['buyer_sms_notify'])))
            && !is_mobile_woo_sms($_POST['billing_phone'])
        ) {
            wc_add_notice(__('شماره موبایل معتبر نیست .'), 'error');
        }

        if (ps_sms_options('allow_buyer_select_status', 'sms_buyer_settings', 'no') == 'yes'
            && ps_sms_options('force_buyer_select_status', 'sms_buyer_settings', 'no') == 'yes'
            && ((ps_sms_options('force_enable_buyer', 'sms_buyer_settings', 'no') != 'yes' && ! empty($_POST['buyer_sms_notify'])) || ps_sms_options('force_enable_buyer', 'sms_buyer_settings', 'no') == 'yes')
            && empty($_POST['buyer_sms_status'])
        )
            wc_add_notice(__('انتخاب حداقل یکی از وضعیت های سفارش دریافت پیامک الزامی است .'), 'error');
    }

    /**
     * Saves SMS Field In Order Meta
     *
     * @param string $order_id order id
     *
     * @return void
     */
    function saveSmsFieldInOrderMeta($order_id)
    {
        if(ps_sms_options('enable_buyer', 'sms_buyer_settings', 'off') == 'off' || count((array) get_allowed_woo_status_ps_sms()) < 0)
            return;

        update_post_meta($order_id, '_force_enable_buyer', ps_sms_options('force_enable_buyer', 'sms_buyer_settings', 'no'));
        update_post_meta($order_id, '_allow_buyer_select_status', ps_sms_options('allow_buyer_select_status', 'sms_buyer_settings', 'no'));

        if (!empty($_POST['buyer_sms_notify']))
            update_post_meta($order_id, '_buyer_sms_notify', 'yes');
        else
            update_post_meta($order_id, '_buyer_sms_notify', 'no');

        if (ps_sms_options('force_enable_buyer', 'sms_buyer_settings', 'no') == 'yes')
            update_post_meta($order_id, '_buyer_sms_notify', 'yes');

        if (!empty($_POST['buyer_sms_status'])) {

            $_buyer_sms_status = is_array($_POST['buyer_sms_status']) ? array_map('sanitize_text_field', $_POST['buyer_sms_status']) : sanitize_text_field($_POST['buyer_sms_status']);
            update_post_meta($order_id, '_buyer_sms_status', $_buyer_sms_status);
        } else if (get_post_meta($order_id, '_buyer_sms_status')) 
            delete_post_meta($order_id, '_buyer_sms_status');
    }

    /**
     * Show SMS Field In Admin Order Meta
     *
     * @param object $order order
     *
     * @return void
     */
    function showSmsFieldInAdminOrderMeta($order)
    {
        if(ps_sms_options('enable_buyer', 'sms_buyer_settings', 'off') == 'off' || count((array) get_allowed_woo_status_ps_sms()) < 0)
            return;
        $want_notification =  get_post_meta($order->id, '_buyer_sms_notify', true);
        $display_info = (isset($want_notification) && !empty($want_notification) && $want_notification == 'yes') ? 'بله' : 'خیر'; 
        $old_status = $order->get_status();

        if ((get_post_meta($order->id, '_force_enable_buyer', true) == 'yes') || (empty($order->id) || ! $order->id) || (empty($old_status) || ! isset($old_status) || $old_status == 'draft' || ! in_array($order->post_status, array_keys(wc_get_order_statuses()))))
            echo '<p>خریدار حق انتخاب دریافت یا عدم دریافت پیامک را ندارد .</p>';
        else
            echo '<p>آیا خریدار مایل به دریافت پیامک هست : ' . $display_info . '</p>';

        if (get_post_meta($order->id, '_allow_buyer_select_status', true) == 'yes') {
            $buyer_sms_status = get_post_meta($order->id, '_buyer_sms_status', true);
            $display_statuses = (isset($buyer_sms_status) && !empty($buyer_sms_status)) ? $buyer_sms_status : array(); 

            echo '<p>وضعیت های انتخابی توسط خریدار برای دریافت پیامک : ';
            if (count($display_statuses) >=0 &&  !empty($display_statuses)) {
                $statuses = '';
                foreach ((array) $display_statuses as $status)
                    $statuses .= wc_get_order_status_name($status).' - ';
                echo substr($statuses, 0, -3);
            } else
                echo 'وضعیتی انتخاب نشده است';
        } else {
            echo '<p>خریدار حق انتخاب وضعیت های دریافت پیامک را ندارد .';
        }
        echo '</p>';
    }

    /**
     * Send SMS When Order Status Changed
     *
     * @param integer $order_id   order id
     * @param integer $old_status old status
     * @param integer $new_status new status
     *
     * @return void
     */
    public function sendSmsWhenOrderStatusChanged($order_id, $old_status, $new_status)
    {
        if (!$order_id)
            return;

        $order_page = (!empty($_POST['shop_order_ipe']) && $_POST['shop_order_ipe'] == 'true') ? true : false;

        $order = new WC_Order($order_id);
        $admin_sms_data = $buyer_sms_data = array();

        $product_list = get_product_list_ps_sms($order);
        $all_items = $product_list['names'] . '__vsh__' . $product_list['names_qty'];

        $buyer_sms_data['number'] = explode(',', get_post_meta($order_id, '_billing_phone', true));
        $buyer_sms_body = $order_page ? (isset($_POST['sms_order_text']) ? esc_textarea($_POST['sms_order_text']) : '') : ps_sms_options('sms_body_' . $new_status, 'sms_buyer_settings', '');
        $buyer_sms_data['sms_body'] = str_replace_tags_order($buyer_sms_body, $new_status, $order_id, $order, $all_items, '');

        // خریدار
        if ($this->buyerCanGetPM($order_id, $new_status)) {
            $buyer_response_sms = WoocommerceIR_Gateways_SMS::init()->sendSMSir($buyer_sms_data);
            if ($buyer_response_sms) {
                $order->add_order_note(sprintf('پیامک با موفقیت به خریدار با شماره %s ارسال گردید', get_post_meta($order_id, '_billing_phone', true)));
            } else {
                $order->add_order_note(sprintf('پیامک بخاطر خطا به خریدار با شماره %s ارسال نشد', get_post_meta($order_id, '_billing_phone', true)));
            }
        }

        // مدیر کل
        if (ps_sms_options('enable_super_admin_sms', 'sms_super_admin_settings', 'on') == 'on') {
            $super_admin_order_status = ps_sms_options('super_admin_order_status', 'sms_super_admin_settings', array());
            if (in_array($new_status, $super_admin_order_status)) {
                $super_admin_sms_body = ps_sms_options('super_admin_sms_body_' . $new_status, 'sms_super_admin_settings', '');
                $super_admin_sms_data['sms_body'] = str_replace_tags_order($super_admin_sms_body, $new_status, $order_id, $order, $all_items, '');
                $super_admin_sms_data['number']   = explode(',', ps_sms_options('super_admin_phone', 'sms_super_admin_settings', ''));
                $super_admin_sms_data['number'] = fa_en_mobile_woo_sms($super_admin_sms_data['number']);

                $super_admin_response_sms = WoocommerceIR_Gateways_SMS::init()->sendSMSir($super_admin_sms_data);
                if ($super_admin_response_sms) {
                    $order->add_order_note(sprintf('پیامک با موفقیت به مدیر کل با شماره %s ارسال گردید', ps_sms_options('super_admin_phone', 'sms_super_admin_settings', '')));
                } else {
                    $order->add_order_note(sprintf('پیامک بخاطر خطا به مدیر کل با شماره %s ارسال نشد', ps_sms_options('super_admin_phone', 'sms_super_admin_settings', '')));
                }
            }
        }

        // مدیر محصول
        if (ps_sms_options('enable_product_admin_sms', 'sms_product_admin_settings', 'on') == 'on') {

            $product_ids = $product_list['ids'];
            $product_ids = explode(',', $product_ids);
            unset($product_admin_numbers_sms);
            $product_admin_numbers_sms = array();

            foreach ((array) $product_ids as $product_id) {
                $admin_datas = maybe_unserialize(get_post_meta($product_id, '_ipeir_woo_products_tabs', true));
                foreach ((array) $admin_datas as $admin_data) {
                    $admin_statuses = array();
                    if (isset($admin_data['content']))
                        $admin_statuses = explode('-sv-', $admin_data['content']);

                    if (in_array($new_status, $admin_statuses)) {
                        if (empty($product_admin_numbers_sms[$admin_data['title']]))
                            $product_admin_numbers_sms[$admin_data['title']] = get_the_title($product_id);
                        else
                            $product_admin_numbers_sms[$admin_data['title']] = $product_admin_numbers_sms[$admin_data['title']].'-'.get_the_title($product_id);
                    }
                }
            }

            if (!empty($product_admin_numbers_sms) && count($product_admin_numbers_sms) > 0) {
                foreach ((array) $product_admin_numbers_sms as $number => $vendor_items) {
                    if (strlen($number) > 5) {
                        $admin_sms_data['number'] = explode(',', $number);
                        $admin_sms_data['number'] = fa_en_mobile_woo_sms($admin_sms_data['number']);
                        $product_admin_sms_body = ps_sms_options('product_admin_sms_body_' . $new_status, 'sms_product_admin_settings', ''); 
                        $admin_sms_data['sms_body'] = str_replace_tags_order($product_admin_sms_body, $new_status, $order_id, $order, $all_items, $vendor_items);
                        $admin_response_sms = WoocommerceIR_Gateways_SMS::init()->sendSMSir($admin_sms_data);
                        if ($admin_response_sms) {
                            $order->add_order_note(sprintf('پیامک با موفقیت به مدیر محصول با شماره %s ارسال گردید', $number));
                        } else {
                            $order->add_order_note(sprintf('پیامک بخاطر خطا به مدیر محصول با شماره %s ارسال نشد', $number));
                        }
                    }
                }
            }
        }
    }

    /**
     * Order Data After Order Details In Admin
     *
     * @param integer $order order
     *
     * @return void
     */
    function adminOrderDataAfterOrderDetails($order)
    { 
        if (ps_sms_options('enable_buyer', 'sms_buyer_settings', 'no') == 'on') { ?>
            <script type="text/javascript">
                jQuery(document).ready(function(){
                    jQuery("#order_status").change(function(){
                        jQuery("#ipe_sms_textbox").html( "<img src=\"<?php echo PS_WOO_SMS_PLUGIN_PATH ?>/assets/images/ajax-loader.gif\" />" );
                        var order_status = jQuery("#order_status").val();
                        jQuery.ajax({
                            url : "<?php echo admin_url("admin-ajax.php") ?>",
                            type : "post",
                            data : {
                                action : "change_sms_text",
                                security: "<?php echo wp_create_nonce("change-sms-text") ?>",
                                order_id : "<?php echo $order->id; ?>",
                                order_status : order_status,
                            },
                            success : function( response ) {
                                jQuery("#ipe_sms_textbox").html( response );
                            }
                        });
                    });
                });
            </script>
            <p class="form-field form-field-wide"  id="ipe_sms_textbox_p" >
                <span id="ipe_sms_textbox" class="ipe_sms_textbox"></span>
            </p>
            <?php
        }
    }

    /**
     * Change SMS Text
     *
     * @return void
     */
    function changeSmsText()
    {
        check_ajax_referer('change-sms-text', 'security');
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $new_status = false;

        if (isset($_POST['order_status'])) {
            $_order_status = is_array($_POST['order_status']) ? array_map('sanitize_text_field', $_POST['order_status']) : sanitize_text_field($_POST['order_status']);
            $new_status = substr($_order_status, 3);
        }

        $buyer_sms_body = ps_sms_options('sms_body_' . $new_status, 'sms_buyer_settings', '');
        $order = new WC_Order($order_id);
        $product_list = get_product_list_ps_sms($order);
        $all_items = $product_list['names'] . '__vsh__' . $product_list['names_qty'];
        $buyer_sms_body = str_replace_tags_order($buyer_sms_body, $new_status, $order_id, $order, $all_items, '');

        if (defined('DOING_AJAX') && DOING_AJAX) {
            echo '<textarea id="sms_order_text" name="sms_order_text" style="width:100%;height:120px;"> ' . $buyer_sms_body . ' </textarea>';
            echo '<input type="hidden" name="shop_order_ipe" value="true" />';
            die();
        } else {
            echo 'خطای آیجکس رخ داده است';
            die();
        }
    }

    /**
     * Buyer Can Get SMS
     *
     * @param integer $order_id   order id
     * @param integer $new_status new status
     *
     * @return boolean
     */
    function buyerCanGetPM($order_id, $new_status)
    {
        $allowed_status  = ps_sms_options('order_status', 'sms_buyer_settings', array());

        if (empty($order_id) || !$order_id) {
            return true;
        } else {
            $order = new WC_Order($order_id);
            $old_status = $order->get_status();

            if (empty($old_status) || !isset($old_status) || $old_status == 'draft' || ! in_array($order->post_status, array_keys(wc_get_order_statuses()))) {
                update_post_meta($order_id, '_force_enable_buyer', 'yes');
                update_post_meta($order_id, '_allow_buyer_select_status', 'no');
                update_post_meta($order_id, '_buyer_sms_notify', 'no');

                if (!(in_array($new_status, $allowed_status) && count($allowed_status) > 0 && count((array) get_allowed_woo_status_ps_sms()) > 0)) 
                    return false;

                return true;
            } else {
                if (!(in_array($new_status, $allowed_status) && count($allowed_status) > 0 && count((array) get_allowed_woo_status_ps_sms()) > 0)) 
                    return false;

                if ((ps_sms_options('enable_buyer', 'sms_buyer_settings', 'off') == 'on') && get_post_meta($order_id, '_buyer_sms_notify', true) == 'yes' && strlen(get_post_meta($order_id, '_billing_phone', true)) > 5) {
                    $buyer_sms_status = get_post_meta($order_id, '_buyer_sms_status', true) ? get_post_meta($order_id, '_buyer_sms_status', true) : array();

                    if ((get_post_meta($order_id, '_allow_buyer_select_status', true) == 'no')
                        || ( get_post_meta($order_id, '_allow_buyer_select_status', true) == 'yes' && in_array($new_status, $buyer_sms_status))
                    ) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}