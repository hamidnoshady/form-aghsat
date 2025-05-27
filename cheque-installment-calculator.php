<?php
/**
 * Plugin Name: محاسبه‌گر اقساط چکی
 * Description: فرم محاسبه اقساط چکی و آپلود مدارک. از شورت‌کد [cheque_calculator_form] استفاده کنید.
 * Version: 1.2
 * Author: شما (یا نام خودتان)
 * Author URI: https://yourwebsite.com
 * Text Domain: cheque-calculator
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// 1. Register Custom Post Type for Submissions
function cic_register_submission_cpt() {
    $labels = array(
        'name'                  => _x( 'محاسبات اقساط', 'Post type general name', 'cheque-calculator' ),
        'singular_name'         => _x( 'محاسبه اقساط', 'Post type singular name', 'cheque-calculator' ),
        'menu_name'             => _x( 'محاسبات اقساط', 'Admin Menu text', 'cheque-calculator' ),
        'name_admin_bar'        => _x( 'محاسبه اقساط', 'Add New on Toolbar', 'cheque-calculator' ),
        'add_new'               => __( 'افزودن محاسبه جدید', 'cheque-calculator' ),
        'add_new_item'          => __( 'افزودن محاسبه اقساط جدید', 'cheque-calculator' ),
        'new_item'              => __( 'محاسبه جدید', 'cheque-calculator' ),
        'edit_item'             => __( 'ویرایش محاسبه', 'cheque-calculator' ),
        'view_item'             => __( 'مشاهده محاسبه', 'cheque-calculator' ),
        'all_items'             => __( 'تمام محاسبات', 'cheque-calculator' ),
        'search_items'          => __( 'جستجوی محاسبات', 'cheque-calculator' ),
        'parent_item_colon'     => __( 'والد محاسبه:', 'cheque-calculator' ),
        'not_found'             => __( 'هیچ محاسبه‌ای یافت نشد.', 'cheque-calculator' ),
        'not_found_in_trash'    => __( 'هیچ محاسبه‌ای در سطل زباله یافت نشد.', 'cheque-calculator' ),
        'featured_image'        => _x( 'تصویر شاخص محاسبه', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'cheque-calculator' ),
        'set_featured_image'    => _x( 'تنظیم تصویر شاخص', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'cheque-calculator' ),
        'remove_featured_image' => _x( 'حذف تصویر شاخص', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'cheque-calculator' ),
        'use_featured_image'    => _x( 'استفاده به عنوان تصویر شاخص', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'cheque-calculator' ),
        'archives'              => _x( 'آرشیو محاسبات', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'cheque-calculator' ),
        'insert_into_item'      => _x( 'درج در محاسبه', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'cheque-calculator' ),
        'uploaded_to_this_item' => _x( 'بارگذاری شده برای این محاسبه', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'cheque-calculator' ),
        'filter_items_list'     => _x( 'فیلتر لیست محاسبات', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'cheque-calculator' ),
        'items_list_navigation' => _x( 'ناوبری لیست محاسبات', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'cheque-calculator' ),
        'items_list'            => _x( 'لیست محاسبات', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'cheque-calculator' ),
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'installment-submission' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'supports'           => array( 'title', 'author' ), 
        'menu_icon'          => 'dashicons-calculator',
        'show_in_rest'       => true, 
    );
    register_post_type( 'installment_calc', $args );
}
add_action( 'init', 'cic_register_submission_cpt' );

// 2. Enqueue Scripts and Styles
function cic_enqueue_scripts() {
    wp_enqueue_style( 'tailwindcss', 'https://cdn.tailwindcss.com', array(), null );
    wp_enqueue_script( 'jalaali-js', 'https://cdn.jsdelivr.net/npm/jalaali-js/dist/jalaali.js', array(), null, true );
    
    wp_enqueue_script( 
        'cheque-calculator-script', 
        plugin_dir_url( __FILE__ ) . 'cheque-calculator-script.js', 
        array(), 
        '1.2.0', // Updated version
        true     
    );

    wp_localize_script( 'cheque-calculator-script', 'cic_ajax_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'cic_calculation_nonce' )
    ));

    // Add custom styles directly for better control within WP context
    $custom_css = "
        body.page-template-default .form-wrapper-wp, 
        body.single-post .form-wrapper-wp, 
        body.archive .form-wrapper-wp { /* Apply to common page/post body classes */
            font-family: 'Vazirmatn', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0; /* Add padding to body if form is full width */
        }
        .form-wrapper-wp { 
            background-color: white; 
            padding: 2rem; 
            border-radius: 1rem; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); 
            width: 100%; 
            max-width: 42rem; /* max-w-2xl */
            margin: 2rem auto;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInSlideUpGlobalWp 0.7s 0.2s ease-out forwards;
        }
        @keyframes fadeInSlideUpGlobalWp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .step-animation { opacity: 0; transform: translateY(15px); animation: fadeInSlideUpStepWp 0.6s ease-out forwards; }
        @keyframes fadeInSlideUpStepWp { to { opacity: 1; transform: translateY(0); } }
        .error-message, .success-message, .info-message {padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; border-width: 1px; opacity:0; animation: fadeInMessageWp 0.5s ease-out forwards;}
        @keyframes fadeInMessageWp { to { opacity: 1; } }
        .error-message {color: #c53030; background-color: #fed7d7; border-color: #f56565;}
        .success-message {color: #2f855a; background-color: #c6f6d5; border-color: #68d391;}
        .info-message {color: #2b6cb0; background-color: #bee3f8; border-color: #63b3ed;}
        .form-label-wp { display: flex; align-items: center; font-weight: 500; color: #374151; margin-bottom: 0.5rem; }
        .form-label-wp svg { width: 1.25rem; height: 1.25rem; margin-left: 0.5rem; color: #6b7280; } /* For RTL, use margin-right */
        html[dir=\"rtl\"] .form-label-wp svg { margin-left: 0; margin-right: 0.5rem; }
        .form-input-custom {border-color: #d1d5db; transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;}
        .form-input-custom:focus {border-color: #4f46e5 !important; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2) !important;}
        .btn {padding-top: 0.625rem; padding-bottom: 0.625rem; padding-left: 1.5rem; padding-right: 1.5rem; border-radius: 0.5rem; font-weight: 700; transition: transform 0.15s ease-out, box-shadow 0.15s ease-out, background-color 0.15s ease-out; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);}
        .btn:hover {transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);}
        .btn-primary {background-image: linear-gradient(to right, #667eea, #764ba2); color: white;}
        .btn-primary:hover {background-image: linear-gradient(to right, #5a6fcf, #6b3e91);}
        .btn-secondary {background-color: #6b7280; color: white;}
        .btn-secondary:hover {background-color: #4b5563;}
        .input-group-animate {opacity: 0; transform: translateX(20px); animation: fadeInSlideLeftWp 0.5s ease-out forwards;}
        html[dir=\"rtl\"] .input-group-animate {transform: translateX(20px);} 
        html[dir=\"ltr\"] .input-group-animate {transform: translateX(-20px);}
        @keyframes fadeInSlideLeftWp { to { opacity: 1; transform: translateX(0);}}
        .file-input-custom {display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; line-height: 1.25rem; color: #4b5563; background-color: #fff; background-clip: padding-box; border: 1px solid #d1d5db; appearance: none; border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;}
        .file-input-custom::file-selector-button {margin-right: -0.75rem; margin-left: 0.75rem; padding: 0.5rem 1rem; font-weight: 600; color: #4f46e5; background-color: #e0e7ff; border-width: 0px; border-style: solid; border-color: inherit; border-radius: 0.375rem 0 0 0.375rem; transition: background-color .15s ease-in-out;}
        html[dir=\"rtl\"] .file-input-custom::file-selector-button {margin-left: -0.75rem; margin-right: 0.75rem; border-radius: 0 0.375rem 0.375rem 0;}
        .file-input-custom:hover::file-selector-button {background-color: #c7d2fe;}
        @keyframes fadeOutSlideDownStepWp { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(15px); } }
        .downpayment-display-box { text-align: center; margin-top: 0.5rem; font-size: 1.25rem; color: #4338ca; font-weight: 700; padding: 0.75rem; background-color: #e0e7ff; border-radius: 0.5rem; box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.06); }
        .table-header-custom {background-color: #f9fafb; color: #4b5563; font-weight: 500;}
        .table-cell-custom {color: #374151;}
    ";
    wp_add_inline_style( 'tailwindcss', $custom_css ); // Add inline styles after Tailwind
}
add_action( 'wp_enqueue_scripts', 'cic_enqueue_scripts' );


// 3. Shortcode to display the form
function cic_calculator_form_shortcode() {
    ob_start();
    ?>
    <div class="form-wrapper-wp">
        <h1 class="text-3xl sm:text-4xl font-bold text-center text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-indigo-600 mb-8">محاسبه‌گر اقساط و آپلود مدارک</h1>

        <div id="formStep1Wp" class="step-animation" style="animation-delay: 0.2s;">
            <div class="space-y-8"> 
                <div class="input-group-animate" style="animation-delay: 0.3s;">
                    <label for="totalPriceSliderWp" class="form-label-wp">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0 .75-.75V9.75M3.75 21v-.75A.75.75 0 0 0 3 19.5h-.75m0 0v.375c0 .621-.504 1.125-1.125 1.125H3.75m0 0h16.5M12 12.75h.008v.008H12v-.008Z" /></svg>
                        مبلغ کل کالا/خدمات (تومان)
                    </label>
                    <input type="range" id="totalPriceSliderWp" name="totalPriceSliderWp" min="5000000" max="100000000" step="5000000" value="5000000" class="mt-1 w-full">
                    <div class="text-center mt-2 text-base text-gray-700 font-medium"> <span id="totalPriceDisplayWp">۵٬۰۰۰٬۰۰۰</span> تومان
                    </div>
                    <input type="hidden" id="totalPriceWp" name="totalPriceWp" value="5000000">
                </div>

                <div class="input-group-animate" style="animation-delay: 0.4s;">
                    <label class="form-label-wp text-center block w-full justify-center">
                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 11.21 12.75 10.5 12 10.5s-1.536.71-2.121 1.256c-1.172.879-1.172 2.303 0 3.182a2.99 2.99 0 0 0 .879.659Z" /></svg>
                        مبلغ پیش پرداخت (۳۰٪ مبلغ کل)
                    </label>
                    <div id="downPaymentDisplayWp" class="downpayment-display-box">
                        ۰ تومان
                    </div>
                    <input type="hidden" id="downPaymentWp" name="downPaymentWp" value="0">
                </div>

                <div class="input-group-animate" style="animation-delay: 0.5s;">
                    <label for="numInstallmentsWp" class="form-label-wp">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-3.75h.008v.008H12v-.008Z" /></svg>
                        تعداد اقساط
                    </label>
                    <select id="numInstallmentsWp" name="numInstallmentsWp" class="mt-1 block w-full px-3 py-3 bg-white border form-input-custom rounded-md shadow-sm focus:outline-none sm:text-sm">
                        <option value="1">۱ قسط</option>
                        <option value="2" selected>۲ قسط</option>
                        <option value="3">۳ قسط</option>
                    </select>
                </div>
            </div>

            <div class="mt-10 flex flex-col sm:flex-row sm:justify-center space-y-4 sm:space-y-0 sm:space-x-4 rtl:sm:space-x-reverse input-group-animate" style="animation-delay: 0.6s;">
                <button id="calculateButtonWp" class="btn btn-primary w-full sm:w-auto">
                    محاسبه اقساط
                </button>
                <button id="resetButtonWp" class="btn btn-secondary w-full sm:w-auto">
                    پاک کردن فرم
                </button>
            </div>
            <div id="messageAreaWp" class="mt-6"></div>
            <div id="resultsAreaWp" class="mt-8 hidden"> 
                <h2 class="text-xl font-semibold text-gray-800 mb-4">نتایج محاسبه:</h2>
                <div class="space-y-3 bg-gray-50 p-4 rounded-lg shadow-md"> 
                    <p class="text-sm"><strong>مبلغ کل کالا/خدمات:</strong> <span id="resultTotalPriceDisplayWp" class="font-semibold"></span> تومان</p>
                    <p class="text-sm"><strong>مبلغ پیش پرداخت:</strong> <span id="resultDownPaymentDisplayWp" class="font-semibold"></span> تومان</p>
                    <p class="text-sm"><strong>مبلغ باقیمانده (کل مبلغ اقساط):</strong> <span id="remainingAmountDisplayWp" class="font-semibold"></span> تومان</p>
                    <p class="text-sm"><strong>مبلغ پایه هر قسط:</strong> <span id="baseInstallmentAmountDisplayWp" class="font-semibold"></span> تومان</p>
                    <p class="text-sm"><strong>کل مدت زمان بازپرداخت:</strong> <span id="totalDurationDisplayWp" class="font-semibold"></span> ماه</p>
                    <p class="text-sm"><strong>تاریخ اولین قسط (یک ماه پس از امروز):</strong> <span id="startDateDisplayInfoWp" class="font-semibold"></span></p>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mt-6 mb-3">جدول زمان‌بندی اقساط:</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border border-gray-300 rounded-lg shadow-md"> 
                        <thead class="table-header-custom">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider">ردیف</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider">تاریخ سررسید (جلالی)</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider">مبلغ قسط (تومان)</th>
                            </tr>
                        </thead>
                        <tbody id="installmentsTableBodyWp" class="bg-white divide-y divide-gray-200">
                        </tbody>
                    </table>
                     <p class="mt-2 text-xs text-gray-600 text-left">توجه: مبلغ قسط آخر ممکن است برای تطابق با مجموع کل، کمی متفاوت باشد.</p>
                </div>
                 <div class="mt-6 text-center">
                    <button id="proceedToUploadButtonWp" class="btn btn-primary w-full sm:w-auto hidden">ادامه و آپلود مدارک</button>
                </div>
            </div>
        </div>

        <div id="formStep2Wp" class="hidden mt-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">آپلود مدارک</h2>
            <div class="space-y-6 bg-gray-50 p-6 rounded-lg shadow-md">
                <div>
                    <label for="chequeImageWp" class="block text-sm form-label mb-2">تصویر چک</label>
                    <input type="file" id="chequeImageWp" name="chequeImageWp" accept="image/*" class="file-input-custom">
                    <img id="chequePreviewWp" src="#" alt="پیش‌نمایش چک" class="mt-3 rounded-md max-h-48 w-auto mx-auto hidden object-contain border border-gray-200 p-1"/>
                </div>
                <div>
                    <label for="nationalIdImageWp" class="block text-sm form-label mb-2">تصویر کارت ملی</label>
                    <input type="file" id="nationalIdImageWp" name="nationalIdImageWp" accept="image/*" class="file-input-custom">
                    <img id="nationalIdPreviewWp" src="#" alt="پیش‌نمایش کارت ملی" class="mt-3 rounded-md max-h-48 w-auto mx-auto hidden object-contain border border-gray-200 p-1"/>
                </div>
            </div>
            <div class="mt-8 flex flex-col sm:flex-row sm:justify-between space-y-3 sm:space-y-0 sm:space-x-3 rtl:sm:space-x-reverse">
                <button id="prevButtonWp" class="btn btn-secondary w-full sm:w-auto">بازگشت به محاسبه</button>
                <button id="submitDocumentsButtonWp" class="btn btn-primary w-full sm:w-auto">ارسال نهایی مدارک</button>
            </div>
        </div>
        <input type="hidden" id="cic_submission_id" value="">
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'cheque_calculator_form', 'cic_calculator_form_shortcode' );

// 4. AJAX Handler for Step 1 (Calculation Submission)
add_action( 'wp_ajax_cic_handle_calculation', 'cic_handle_calculation_submission' );
add_action( 'wp_ajax_nopriv_cic_handle_calculation', 'cic_handle_calculation_submission' ); 

function cic_handle_calculation_submission() {
    check_ajax_referer( 'cic_calculation_nonce', 'nonce' );

    $total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;
    $down_payment = isset($_POST['down_payment']) ? floatval($_POST['down_payment']) : 0;
    $num_installments = isset($_POST['num_installments']) ? intval($_POST['num_installments']) : 0;
    
    $remaining_amount = isset($_POST['remaining_amount']) ? floatval($_POST['remaining_amount']) : 0;
    $base_installment = isset($_POST['base_installment']) ? floatval($_POST['base_installment']) : 0;
    $last_installment = isset($_POST['last_installment']) ? floatval($_POST['last_installment']) : 0;
    $total_duration = isset($_POST['total_duration']) ? intval($_POST['total_duration']) : 0;
    $first_installment_date_jalali = isset($_POST['first_installment_date_jalali']) ? sanitize_text_field($_POST['first_installment_date_jalali']) : '';
    $installments_schedule_json = isset($_POST['installments_schedule']) ? wp_unslash($_POST['installments_schedule']) : '[]'; 
    
    $installments_schedule = json_decode($installments_schedule_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $installments_schedule = array(); 
    }

    if ($total_price <= 0 || $num_installments <= 0) {
        wp_send_json_error( array( 'message' => 'اطلاعات ارسال شده نامعتبر است.' ) );
        return;
    }

    $post_title = 'محاسبه اقساط - ' . date_i18n('Y/m/d H:i:s');
    $post_content = "محاسبه اقساط برای مبلغ کل: " . number_format_i18n($total_price) . " تومان";

    $post_data = array(
        'post_title'   => sanitize_text_field($post_title),
        'post_content' => wp_kses_post($post_content),
        'post_status'  => 'publish', 
        'post_type'    => 'installment_calc',
        'post_author'  => get_current_user_id(), 
    );

    $post_id = wp_insert_post( $post_data );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( array( 'message' => 'خطا در ذخیره سازی محاسبه: ' . $post_id->get_error_message() ) );
    } else {
        update_post_meta( $post_id, '_total_price', $total_price );
        update_post_meta( $post_id, '_down_payment', $down_payment );
        update_post_meta( $post_id, '_num_installments', $num_installments );
        update_post_meta( $post_id, '_remaining_amount', $remaining_amount );
        update_post_meta( $post_id, '_base_installment_amount', $base_installment );
        update_post_meta( $post_id, '_last_installment_amount', $last_installment );
        update_post_meta( $post_id, '_total_duration_months', $total_duration );
        update_post_meta( $post_id, '_first_installment_date_jalali', $first_installment_date_jalali );
        update_post_meta( $post_id, '_installments_schedule', $installments_schedule ); 

        wp_send_json_success( array( 
            'message' => 'محاسبه اقساط با موفقیت ذخیره شد.',
            'submission_id' => $post_id 
        ) );
    }
    wp_die(); 
}

add_action( 'wp_ajax_cic_handle_document_upload', 'cic_handle_document_upload_submission' );
add_action( 'wp_ajax_nopriv_cic_handle_document_upload', 'cic_handle_document_upload_submission' );

function cic_handle_document_upload_submission() {
    check_ajax_referer( 'cic_calculation_nonce', 'nonce' ); 

    $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;

    if (empty($submission_id) || get_post_type($submission_id) !== 'installment_calc') {
        wp_send_json_error(array('message' => 'شناسه محاسبه نامعتبر است.'));
        return;
    }
    
    $files_uploaded_successfully = true; 
    $cheque_file_url = '';
    $national_id_file_url = '';

    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    if (isset($_FILES['cheque_image_wp'])) {
        $uploadedfile_cheque = $_FILES['cheque_image_wp'];
        $upload_overrides = array( 'test_form' => false );
        $movefile_cheque = wp_handle_upload( $uploadedfile_cheque, $upload_overrides );
        if ( $movefile_cheque && ! isset( $movefile_cheque['error'] ) ) {
            $cheque_file_url = $movefile_cheque['url'];
            update_post_meta($submission_id, '_cheque_image_url', $cheque_file_url);
        } else {
            $files_uploaded_successfully = false;
            wp_send_json_error(array('message' => 'خطا در آپلود تصویر چک: ' . (isset($movefile_cheque['error']) ? $movefile_cheque['error'] : 'خطای نامشخص')));
            return;
        }
    }

    if (isset($_FILES['national_id_image_wp'])) {
        $uploadedfile_id = $_FILES['national_id_image_wp'];
        $upload_overrides = array( 'test_form' => false );
        $movefile_id = wp_handle_upload( $uploadedfile_id, $upload_overrides );
        if ( $movefile_id && ! isset( $movefile_id['error'] ) ) {
            $national_id_file_url = $movefile_id['url'];
            update_post_meta($submission_id, '_national_id_image_url', $national_id_file_url);
        } else {
            $files_uploaded_successfully = false;
            wp_send_json_error(array('message' => 'خطا در آپلود تصویر کارت ملی: ' . (isset($movefile_id['error']) ? $movefile_id['error'] : 'خطای نامشخص')));
            return;
        }
    }

    if($files_uploaded_successfully){
        wp_send_json_success(array('message' => 'مدارک با موفقیت آپلود و ذخیره شدند.'));
    }
    wp_die();
}

add_filter( 'manage_installment_calc_posts_columns', 'cic_set_custom_edit_installment_calc_columns' );
function cic_set_custom_edit_installment_calc_columns($columns) {
    $new_columns = array();
    $new_columns['title'] = $columns['title']; 
    $new_columns['total_price'] = __( 'مبلغ کل', 'cheque-calculator' );
    $new_columns['down_payment'] = __( 'پیش پرداخت', 'cheque-calculator' );
    $new_columns['num_installments'] = __( 'تعداد اقساط', 'cheque-calculator' );
    $new_columns['submission_date'] = $columns['date']; 
    
    unset($columns['title']);
    unset($columns['date']);

    return array_merge($new_columns, $columns); 
}

add_action( 'manage_installment_calc_posts_custom_column' , 'cic_custom_installment_calc_column', 10, 2 );
function cic_custom_installment_calc_column( $column, $post_id ) {
    switch ( $column ) {
        case 'total_price' :
            echo esc_html( number_format_i18n(floatval(get_post_meta( $post_id , '_total_price' , true ))) ) . ' تومان'; 
            break;
        case 'down_payment' :
            echo esc_html( number_format_i18n(floatval(get_post_meta( $post_id , '_down_payment' , true ))) ) . ' تومان'; 
            break;
        case 'num_installments' :
            echo esc_html( intval(get_post_meta( $post_id , '_num_installments' , true )) ) . ' قسط'; 
            break;
    }
}

?>
