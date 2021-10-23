<?php
/**
 * WHMCS SDK Sample Provisioning Module
 *
 * Provisioning Modules, also referred to as Product or Server Modules, allow
 * you to create modules that allow for the provisioning and management of
 * products and services in WHMCS.
 *
 * This sample file demonstrates how a provisioning module for WHMCS should be
 * structured and exercises all supported functionality.
 *
 * Provisioning Modules are stored in the /modules/servers/ directory. The
 * module name you choose must be unique, and should be all lowercase,
 * containing only letters & numbers, always starting with a letter.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "suble" and therefore all
 * functions begin "suble_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _ConfigOptions
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/provisioning-modules/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see https://developers.whmcs.com/provisioning-modules/meta-data-params/
 *
 * @return array
 */

function suble_MetaData()
{
    return array(
        'DisplayName' => 'Suble.io',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '1111', // Default Non-SSL Connection Port
        'DefaultSSLPort' => '1112', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
    );
}

/**
 * Define product configuration options.
 *
 * The values you return here define the configuration options that are
 * presented to a user when configuring a product for use with the module. These
 * values are then made available in all module function calls with the key name
 * configoptionX - with X being the index number of the field from 1 to 24.
 *
 * You can specify up to 24 parameters, with field types:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each and their possible configuration parameters are provided in
 * this sample function.
 *
 * @see https://developers.whmcs.com/provisioning-modules/config-options/
 *
 * @return array
 */
function suble_ConfigOptions()
{
    return array(
        //Suble VM Package
        'Type' => array(
            'Type' => 'dropdown',
            'Options' => 'Virtual_Machine,Floating_IP,Network',
            'Default' => 'Virtual_Machine',
            'Description' => 'Product Type',
        ),
        'Package' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'micro',
            'Description' => 'Product Package',
        ),
        //Reseller Id
        'Reseller ID' => array(
            'Type' => 'text',
            'Size' => '24',
            'Default' => '',
            'Description' => 'Suble.io Reseller ID',
        ),
        //Reseller Api Key
        'Reseller API Key' => array(
            'Type' => 'text',
            'Size' => '24',
            'Default' => '',
            'Description' => 'Suble.io Reseller API Key',
        ),
    );
}

/**
 * Provision a new instance of a product/service.
 *
 * Attempt to provision a new instance of a given product/service. This is
 * called any time provisioning is requested inside of WHMCS. Depending upon the
 * configuration, this can be any of:
 * * When a new order is placed
 * * When an invoice for a new order is paid
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function suble_CreateAccount(array $params)
{
    try {
       //if($param["configoption1"] == "Virtual_Machine") {
            $sessionParsed = json_decode(
                HTTPRequester::HTTPPost(
                    "https://api.suble.io/projects/".$params["configoption3"]."/reseller/order/vm",
                    array(
                        "productid" => $params["accountid"],
                        "package" => $params["configoption2"],
                        "os" => "".$params["customfields"]["os"],
                        "productname" => "".$params["customfields"]["name"],

                        "name" => $params["clientsdetails"]["fullname"],
                        "email" => $params["clientsdetails"]["email"],
                        "uuid" => $params["clientsdetails"]["uuid"],
                    ),
                    $params["configoption4"]
                ),
                true
            );
            return 'success';
       // }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'suble',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Suspend an instance of a product/service.
 *
 * Called when a suspension is requested. This is invoked automatically by WHMCS
 * when a product becomes overdue on payment or can be called manually by admin
 * user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function suble_SuspendAccount(array $params)
{
    $err = "success";
    try {
        // Call the service's suspend function, using the values provided by
        // WHMCS in `$params`.
        $responseData = json_decode(
            HTTPRequester::HTTPPost(
                "https://api.suble.io/projects/".$params["configoption3"]."/reseller/products/".$params["accountid"]."/suspend",
                array(),
                $params["configoption4"]
            ),
            true
        );
        if(property_exists($responseData, "error")) {
            $err = $responseData["error"];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'suble',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $err = $e->getMessage();
    }

    return $err;
}

/**
 * Un-suspend instance of a product/service.
 *
 * Called when an un-suspension is requested. This is invoked
 * automatically upon payment of an overdue invoice for a product, or
 * can be called manually by admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function suble_UnsuspendAccount(array $params)
{
    $err = "success";
    try {
        $responseData = json_decode(
            HTTPRequester::HTTPDelete(
                "https://api.suble.io/projects/".$params["configoption3"]."/reseller/products/".$params["accountid"]."/suspend",
                array(),
                $params["configoption4"]
            ),
            true
        );
        if(property_exists($responseData, "error")) {
            $err = $responseData["error"];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'suble',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $err = $e->getMessage();
    }

    return $err;
}

/**
 * Terminate instance of a product/service.
 *
 * Called when a termination is requested. This can be invoked automatically for
 * overdue products if enabled, or requested manually by an admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function suble_TerminateAccount(array $params)
{
    try {
        $sessionParsed = json_decode(
            HTTPRequester::HTTPDelete(
                "https://api.suble.io/projects/".$params["configoption3"]."/reseller/products/".$params["accountid"],
                array(),
                $params["configoption4"]
            ),
            true
        );
        return 'success';
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'suble',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }

    return 'success';
}

/**
 * Upgrade or downgrade an instance of a product/service.
 *
 * Called to apply any change in product assignment or parameters. It
 * is called to provision upgrade or downgrade orders, as well as being
 * able to be invoked manually by an admin user.
 *
 * This same function is called for upgrades and downgrades of both
 * products and configurable options.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function suble_ChangePackage(array $params)
{
    $err = "success";
    try {
        $responseData = json_decode(
            HTTPRequester::HTTPPost(
                "https://api.suble.io/projects/".$params["configoption3"]."/reseller/products/".$params["accountid"]."/package",
                array(
                    "package" => $params["configoption2"]
                ),
                $params["configoption4"]
            ),
            true
        );
        if(property_exists($responseData, "error")) {
            $err = $responseData["error"];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'suble',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return $err;
}

/**
 * Test connection with the given server parameters.
 *
 * Allows an admin user to verify that an API connection can be
 * successfully made with the given configuration parameters for a
 * server.
 *
 * When defined in a module, a Test Connection button will appear
 * alongside the Server Type dropdown when adding or editing an
 * existing server.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function suble_TestConnection(array $params)
{
    try {
        // Call the service's connection test function.

        $success = true;
        $errorMsg = '';
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'suble',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}

/**
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 * The template file you return can be one of two types:
 *
 * * tabOverviewModuleOutputTemplate - The output of the template provided here
 *   will be displayed as part of the default product/service client area
 *   product overview page.
 *
 * * tabOverviewReplacementTemplate - Alternatively using this option allows you
 *   to entirely take control of the product/service overview page within the
 *   client area.
 *
 * Whichever option you choose, extra template variables are defined in the same
 * way. This demonstrates the use of the full replacement.
 *
 * Please Note: Using tabOverviewReplacementTemplate means you should display
 * the standard information such as pricing and billing details in your custom
 * template or they will not be visible to the end user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function suble_ClientArea(array $params)
{
    try {
        $sessionParsed = json_decode(HTTPRequester::HTTPGet("https://api.suble.io/projects/".$params["configoption3"]."/reseller/users/".$params["clientsdetails"]["uuid"]."/session", array(), $params["configoption4"]), true);
        $authLink = "https://".$sessionParsed["domain"]."/auth?auth=".$sessionParsed["token"];
        return array(
            'templatefile' => 'templates/overview.tpl',
            'vars' => array(
                'authLink' => $authLink
            ),
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'suble',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}

//https://stackoverflow.com/a/45494300/12746977
class HTTPRequester {
    /**
     * @description Make HTTP-GET call
     * @param       $url
     * @param       array $params
     * @return      HTTP-Response body or an empty string if the request fails or is empty
     */
    public static function HTTPGet($url, array $params, string $auth) {
        $query = http_build_query($params); 
        $ch    = curl_init($url.'?'.$query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array('Authorization: Bearer '.$auth, 'Content-type: application/json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    /**
     * @description Make HTTP-POST call
     * @param       $url
     * @param       array $params
     * @return      HTTP-Response body or an empty string if the request fails or is empty
     */
    public static function HTTPPost($url, array $params, string $auth) {
        $ch    = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = array('Authorization: Bearer '.$auth, 'Content-type: application/json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    /**
     * @description Make HTTP-PUT call
     * @param       $url
     * @param       array $params
     * @return      HTTP-Response body or an empty string if the request fails or is empty
     */
    public static function HTTPPut($url, array $params, string $auth) {
        $ch    = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array('Authorization: Bearer '.$auth, 'Content-type: application/json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    /**
     * @category Make HTTP-DELETE call
     * @param    $url
     * @param    array $params
     * @return   HTTP-Response body or an empty string if the request fails or is empty
     */
    public static function HTTPDelete($url, array $params, string $auth) {
        $ch    = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array('Authorization: Bearer '.$auth, 'Content-type: application/json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}