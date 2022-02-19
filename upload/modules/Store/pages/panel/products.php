<?php
/*
 *  Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel products page
 */

// Can the user view the StaffCP?
if(!$user->handlePanelPageLoad('staffcp.store.products')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_products');
$page_title = $store_language->get('general', 'products');
require_once(ROOT_PATH . '/core/templates/backend_init.php');
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');

$store = new Store($cache, $store_language);

if(!isset($_GET['action'])) {
    // Get all products and categories
    $categories = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE deleted = 0 ORDER BY `order` ASC', array());
    $all_categories = [];

    if($categories->count()){
        $categories = $categories->results();

        $currency = Output::getClean($configuration->get('store', 'currency'));
        $currency_symbol = Output::getClean($configuration->get('store', 'currency_symbol'));

        foreach($categories as $category){
            $new_category = array(
                'name' => Output::getClean(Output::getDecoded($category->name)),
                'products' => array(),
                'edit_link' => URL::build('/panel/store/categories/', 'action=edit&id=' . Output::getClean($category->id)),
                'delete_link' => URL::build('/panel/store/categories/', 'action=delete&id=' . Output::getClean($category->id))
            );

            $products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE category_id = ? AND deleted = 0 ORDER BY `order` ASC', array(Output::getClean($category->id)));

            if($products->count()){
                $products = $products->results();

                foreach($products as $product){
                    $new_product = array(
                        'id' => Output::getClean($product->id),
                        'id_x' => str_replace('{x}', Output::getClean($product->id), $store_language->get('admin', 'id_x')),
                        'name' => Output::getClean($product->name),
                        'price' => Output::getClean($product->price),
                        'edit_link' => URL::build('/panel/store/product/', 'product=' . Output::getClean($product->id)),
                        'delete_link' => URL::build('/panel/store/product/', 'product=' . Output::getClean($product->id))
                    );

                    $new_category['products'][] = $new_product;
                }
            }

            $all_categories[] = $new_category;
        }
        
    } else {
        $smarty->assign('NO_PRODUCTS', $store_language->get('general', 'no_products'));
    }

    $smarty->assign(array(
        'ALL_CATEGORIES' => $all_categories,
        'CURRENCY' => $currency,
        'CURRENCY_SYMBOL' => $currency_symbol,
        'NEW_CATEGORY' => $store_language->get('admin', 'new_category'),
        'NEW_CATEGORY_LINK' => URL::build('/panel/store/categories/', 'action=new'),
        'NEW_PRODUCT' => $store_language->get('admin', 'new_product'),
        'NEW_PRODUCT_LINK' => URL::build('/panel/store/products/', 'action=new'),
        'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
        'CONFIRM_DELETE_CATEGORY' => $store_language->get('admin', 'category_confirm_delete'),
        'CONFIRM_DELETE_PRODUCT' => $store_language->get('admin', 'product_confirm_delete'),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no'),
    ));
    
    $template->addJSFiles(array(
        (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/js/jquery-ui.min.js' => array()
    ));

    $template_file = 'store/products.tpl';
} else {
    switch($_GET['action']) {
        case 'new';
            // Create new product
            if(Input::exists()){
                $errors = array();
                if(Token::check(Input::get('token'))){
                    $validate = new Validate();
                    $validation = $validate->check($_POST, array(
                        'name' => array(
                            'required' => true,
                            'min' => 1,
                            'max' => 128
                        ),
                        'description' => array(
                            'max' => 100000
                        )
                    ));
                    
                    if ($validation->passed()){
                        // Validate if category exist
                        $category = DB::getInstance()->query('SELECT id FROM nl2_store_categories WHERE id = ?', array(Input::get('category')))->results();
                        if(!count($category)) {
                            $errors[] = $store_language->get('admin', 'invalid_category');
                        }
                        
                        // Get price
                        if(!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.00 || $_POST['price'] > 1000 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
                            $errors[] = $store_language->get('admin', 'invalid_price');
                        } else {
                            $price = number_format($_POST['price'], 2, '.', '');
                        }

                        // insert into database if there is no errors
                        if(!count($errors)) {
                            // Get last order
                            $last_order = DB::getInstance()->query('SELECT * FROM nl2_store_products ORDER BY `order` DESC LIMIT 1')->results();
                            if(count($last_order)) $last_order = $last_order[0]->order;
                            else $last_order = 0;
                            
                            // Hide category?
                            if(isset($_POST['hidden']) && $_POST['hidden'] == 'on') $hidden = 1;
                            else $hidden = 0;
                            
                            // Disable category?
                            if(isset($_POST['disabled']) && $_POST['disabled'] == 'on') $disabled = 1;
                            else $disabled = 0;

                            // Save to database
                            $queries->create('store_products', array(
                                'name' => Output::getClean(Input::get('name')),
                                'description' => Output::getClean(Input::get('description')),
                                'category_id' => $category[0]->id,
                                'price' => $price,
                                'hidden' => $hidden,
                                'disabled' => $disabled,
                                'order' => $last_order + 1,
                            ));
                            $lastId = $queries->getLastId();
                            $product = new Product($lastId);
                            
                            // Add the selected connections, if isset
                            if(isset($_POST['connections']) && is_array($_POST['connections'])) {
                                foreach ($_POST['connections'] as $connection) {
                                    if (!array_key_exists($connection, $product->getConnections())) {
                                        $product->addConnection($connection);
                                    }
                                }
                            }
                            
                            // Add the selected fields, if isset
                            if(isset($_POST['fields']) && is_array($_POST['fields'])) {
                                foreach ($_POST['fields'] as $field) {
                                    if (!array_key_exists($field, $product->getFields())) {
                                        $product->addField($field);
                                    }
                                }
                            }
                            
                            Session::flash('products_success', $store_language->get('admin', 'product_created_successfully'));
                            Redirect::to(URL::build('/panel/store/product/', 'product=' . $lastId));
                            die();
                        }
                    } else {
                        $errors[] = $store_language->get('admin', 'description_max_100000');
                    }
                } else {
                    // Invalid token
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }
            
            // Connections
            $connections_array = array();
            $connections = DB::getInstance()->query('SELECT * FROM nl2_store_connections')->results();
            foreach($connections as $connection){
                $connections_array[] = array(
                    'id' => Output::getClean($connection->id),
                    'name' => Output::getClean($connection->name),
                    'selected' => ((isset($_POST['connections']) && is_array($_POST['connections'])) ? in_array($connection->id, $_POST['connections']) : false)
                );
            }
            
            // Fields
            $fields_array = array();
            $fields = DB::getInstance()->query('SELECT * FROM nl2_store_fields WHERE deleted = 0')->results();
            foreach($fields as $field){
                $fields_array[] = array(
                    'id' => Output::getClean($field->id),
                    'identifier' => Output::getClean($field->identifier),
                    'selected' => ((isset($_POST['fields']) && is_array($_POST['fields'])) ? in_array($field->id, $_POST['fields']) : false)
                );
            }
            
            $smarty->assign(array(
                'PRODUCT_TITLE' => $store_language->get('admin', 'new_product'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/products/'),
                'PRODUCT_NAME' => $store_language->get('admin', 'product_name'),
                'PRODUCT_NAME_VALUE' => ((isset($_POST['name']) && $_POST['name']) ? Output::getClean(Input::get('name')) : ''),
                'PRODUCT_DESCRIPTION' => $store_language->get('admin', 'product_description'),
                'PRODUCT_DESCRIPTION_VALUE' => ((isset($_POST['description']) && $_POST['description']) ? Output::getClean(Input::get('description')) : ''),
                'PRICE' => $store_language->get('admin', 'price'),
                'PRODUCT_PRICE_VALUE' => ((isset($_POST['price']) && $_POST['price']) ? Output::getClean(Input::get('price')) : ''),
                'CATEGORY' => $store_language->get('admin', 'category'),
                'CATEGORY_LIST' => $store->getAllCategories(),
                'CONNECTIONS' => $store_language->get('admin', 'connections') . ' ' . $store_language->get('admin', 'select_multiple_with_ctrl'),
                'CONNECTIONS_LIST' => $connections_array,
                'FIELDS' => $store_language->get('admin', 'fields') . ' ' . $store_language->get('admin', 'select_multiple_with_ctrl'),
                'FIELDS_LIST' => $fields_array,
                'CURRENCY' => Output::getClean($configuration->get('store', 'currency')),
                'HIDE_PRODUCT' => $store_language->get('admin', 'hide_product_from_store'),
                'HIDE_PRODUCT_VALUE' => ((isset($_POST['hidden'])) ? 1 : 0),
                'DISABLE_PRODUCT' => $store_language->get('admin', 'disable_product'),
                'DISABLE_PRODUCT_VALUE' => ((isset($_POST['disabled'])) ? 1 : 0),
            ));
            
            $template->addJSFiles(array(
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => array()
            ));

            $template->addJSScript(Input::createEditor('inputDescription'));
            
            $template_file = 'store/products_form.tpl';
        break;
        default:
            Redirect::to(URL::build('/panel/store/products'));
            die();
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

if(Session::exists('products_success'))
    $success = Session::flash('products_success');

if(isset($success))
    $smarty->assign(array(
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ));

if(isset($errors) && count($errors))
    $smarty->assign(array(
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ));

$smarty->assign(array(
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'STORE' => $store_language->get('general', 'store'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'PRODUCTS' => $store_language->get('general', 'products')
));

$template->addCSSFiles(array(
    (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.css' => array()
));

$template->addJSFiles(array(
    (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.js' => array()
));

$template->addJSScript('
    var elems = Array.prototype.slice.call(document.querySelectorAll(\'.js-switch\'));
    elems.forEach(function(html) {
        var switchery = new Switchery(html, {color: \'#23923d\', secondaryColor: \'#e56464\'});
    });
');

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);