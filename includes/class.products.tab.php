<?php

/**
 * Tab Class Page
 * 
 * PHP version 5.6.x | 7.x | 8.x
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */

/**
 * Tab Class
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */
class WoocommerceIR_Tab_SMS
{
    private $_tab_data = false;

    /**
     * Class Constructor For Adding Actions
     *
     * @return void
     */
    public function __construct()
    {
        add_action('woocommerce_product_write_panel_tabs', array($this, 'renderCustomProductTabs'));
        add_action('woocommerce_product_write_panels', array($this, 'productPageIpeCustomTabsPanel'));
        add_action('woocommerce_process_product_meta', array($this, 'productSaveData'), 10, 2);
    }

    /**
     * Render Custom Product Tabs
     *
     * @return void
     */
    public function renderCustomProductTabs()
    {
        echo "<li class=\"ipe_wc_product_tabs_tab\"><a href=\"#persian_woo_hs\">" . __('پیامک', 'persianwoosms') . "</a></li>";
    }

    /**
     * Product Page Ipe Custom Tabs Panel
     *
     * @return boolean
     */
    public function productPageIpeCustomTabsPanel()
    {
        global $post;
        if (defined('WOOCOMMERCE_VERSION') && version_compare(WOOCOMMERCE_VERSION, '2.1', '<')) { ?>
            <style type="text/css">#woocommerce-product-data ul.product_data_tabs li.ipe_wc_product_tabs_tab a { padding:5px 5px 5px 28px;background-repeat:no-repeat;background-position:5px 7px; }</style>
            <?php
        }

        $_tab_data = maybe_unserialize(get_post_meta($post->ID, '_ipeir_woo_products_tabs', true));

        if (empty($_tab_data)) {
            $_tab_data = array(1 => array( 'title' => '', 'content' => '', 'duplicate' => ''));
        }
        $i = 1;

        echo '<div id="persian_woo_hs" class="panel wc-metaboxes-wrapper woocommerce_options_panel">';
        do_action('woocommerce_product_sms', $post->ID);
        echo '<p>شماره های افرادی که مایل به دریافت اطلاعات فروش از طریق پیامک هستید را وارد نمایید. <br>برای انتخاب وضعیت های دریافت پیامک نیز از دکمه Control به همراه کلیک چپ استفاده کنید .</p>';

        if ((ps_sms_options('enable_product_admin_sms', 'sms_product_admin_settings', 'on') == 'on')) {
            foreach ($_tab_data as $tab) {
                if ($i != 1) { ?>
                    <section class="button-holder-sms" alt="<?php echo $i; ?>">
                        <a href="#" onclick="return false;" class="button-secondary number_of_tabs_sms">
                        <span class="dashicons dashicons-no-alt" style="line-height:1.3;"></span><?php echo __('حذف گیرنده', 'persianwoosms'); ?></a>
                    </section>
                <?php } else { ?>
                <section class="button-holder-sms" alt="<?php echo $i; ?>"></section>
                <?php }
                woocommerce_wp_text_input(array('id' => '_ipeir_wc_custom_repeatable_product_tabs_tab_title_' . $i , 'label' => __('شماره گیرنده', 'persianwoosms'), 'description' => '', 'value' => $tab['title'] , 'placeholder' => 'با کاما جدا کنید' , 'class' => 'ipeir_woo_tabs_title_field'));
                $this->woocommerceSelectStatus(array('id' => '_ipeir_wc_custom_repeatable_product_tabs_tab_content_' . $i , 'label' => __('وضعیت', 'persianwoosms'), 'placeholder' => __('', 'persianwoosms'), 'value' => $tab['content'], 'style' => 'width:70%;height:10.5em;' , 'class' => 'ipeir_woo_tabs_content_field'));
if ($i != count($_tab_data)) { 
                    echo '<div class="ipeir-woo-custom-tab-divider"></div>';
}
                $i++;
            }
            ?>
            <div id="duplicate_this_row_sms">
                <a href="#" onclick="return false;" class="button-secondary number_of_tabs_sms" style="float:right;margin-right:4.25em;"><span class="dashicons dashicons-no-alt" style="line-height:1.3;"></span><?php echo __('حذف گیرنده', 'persianwoosms'); ?></a>
                <?php
                woocommerce_wp_text_input(array('id' => 'hidden_duplicator_row_title' , 'label' => __('شماره گیرنده', 'persianwoosms'), 'description' => '', 'placeholder' => 'با کاما جدا کنید' , 'class' => 'ipeir_woo_tabs_title_field'));
                $this->woocommerceSelectStatus(array('id' => 'hidden_duplicator_row_content' , 'label' => __('وضعیت', 'persianwoosms'), 'placeholder' => __('', 'persianwoosms'), 'style' => 'width:70%;height:10.5em;' , 'class' => 'ipeir_woo_tabs_content_field'));
                ?>
                <section class="button-holder-sms" alt="<?php echo $i; ?>"></section>
            </div>
            <p>
                <label style="display:block;" for="_ipeir_wc_custom_repeatable_product_tabs_tab_content_<?php echo $i; ?>"></label>
                <a href="#" class="button-secondary" id="add_another_sms_tab"><em class="dashicons dashicons-plus-alt" style="line-height:1.8;font-size:14px;"></em><?php echo __('افزودن گیرنده', 'persianwoosms'); ?></a>
            </p>
            <?php
            echo '<input type="hidden" value="' . count($_tab_data) . '" id="number_of_tabs_sms" name="number_of_tabs_sms" >';
        }
        echo '</div>';
    }

    /**
     * Product Save Data
     *
     * @param integer $_post_id the post id
     * @param string  $arg      arguman
     *
     * @return void
     */
    public function productSaveData( $_post_id = 0, $arg = '' )
    {
        global $thepostid, $post;

        $the_post_id = $_post_id;

        if (!$the_post_id && !empty($thepostid)) $the_post_id = $thepostid;
        if (!$the_post_id && is_object($post)) $the_post_id = $post->ID;

        if (ps_sms_options('enable_product_admin_sms', 'sms_product_admin_settings', 'on') == 'on') {
            $_tab_data = array();
            $number_of_tabs_sms = intval($_POST['number_of_tabs_sms']);
            $new_number_of_tab = 1;
            $i = 1;
            $j = 1;
            for ($i = 1; $i <= $number_of_tabs_sms; $i++) {
                if (!empty($_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_title_'.$i])) {
                    $new_number_of_tab = $j;

                    if (is_array($_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_title_'.$j])) {
                        $_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_title_'.$j] = array_map('sanitize_text_field', $_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_title_'.$i]);
                    } else {
                        $_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_title_'.$j] = sanitize_text_field($_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_title_'.$i]);
                    }

                    if (!empty($_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_content_'.$j])) {
                        if (is_array($_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_content_'.$j])) {
                            $_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_content_'.$j] = array_map('sanitize_text_field', $_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_content_'.$i]);
                        } else {
                            $_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_content_'.$j] = sanitize_text_field($_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_content_'.$i]);
                        }
                    }
                    $j++;
                }
            }
            $j = 1;
            while ($j <= $new_number_of_tab) {
                $tab_title = stripslashes($_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_title_'.$j]);
                $tab_content = isset($_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_content_'.$j]) ? implode('-sv-', ((array) $_POST['_ipeir_wc_custom_repeatable_product_tabs_tab_content_'.$j])) : '';

                if (empty($tab_title) && empty($tab_content)) {
                    unset($_tab_data[$j]);
                } elseif (!empty($tab_title) || !empty($tab_content)) {
                    $tab_id = '';
                    if ($tab_title) {
                        if (strlen($tab_title) != strlen(utf8_encode($tab_title))) {
                            $tab_id = "tab-custom-" . $j;
                        } else {
                            $tab_id = strtolower($tab_title);
                            $tab_id = preg_replace("/[^\w\s]/", '', $tab_id);
                            $tab_id = preg_replace("/_+/", ' ', $tab_id);
                            $tab_id = preg_replace("/\s+/", '-', $tab_id);
                            $tab_id = 'tab-' . $tab_id;
                        }
                    }
                    $_tab_data[$j] = array('title' => $tab_title, 'id' => $tab_id, 'content' => $tab_content );
                }
                $j++;
            }
            $_tab_data = array_values($_tab_data);

            update_post_meta($the_post_id, '_ipeir_woo_products_tabs', $_tab_data);
        }
    }

    /**
     * Woocommerce Select Status
     *
     * @param array $field field array
     *
     * @return void
     */
    public function woocommerceSelectStatus($field)
    {
        global $thepostid, $post;

        if (!$thepostid ) $thepostid = $post->ID;
        if (!isset($field['placeholder'])) $field['placeholder'] = '';
        if (!isset($field['class'])) $field['class'] = 'short';
        if (!isset($field['value'])) $field['value'] = get_post_meta($thepostid, $field['id'], true);

        echo '<p class="form-field ' . $field['id'] . '_field"><label style="display:block;" for="' . $field['id'] . '">' . $field['label'] . '</label>';
        echo '<select style="height: 200px;" multiple="multiple" class="' . $field['class'] . '" name="' . $field['id'] . '[]" id="' . $field['id'] . '" ' .  '>';

        $selected_statuses = isset($field['value']) ? explode('-sv-', $field['value']) : array();
        $statuses = function_exists('get_all_woo_status_ps_sms_for_product_admin') ? get_all_woo_status_ps_sms_for_product_admin() : array();

        if ($statuses) foreach ($statuses as $status_value => $status_name) {
            echo '<option value="' . esc_attr($status_value) . '"' . selected(in_array($status_value, $selected_statuses), true, false) . '>' . esc_attr($status_name) . '</option>';
        }
        echo '</select></p>';
    }
}