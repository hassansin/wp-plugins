<?php
if ( function_exists( 'isValidUrl' ) ) {
    function isValidUrl( $url ) {
        if ( empty( $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === FALSE )
            return false;
        return true;
    }
}

if ( !function_exists( 'getRequestUri' ) ) {
   function getRequestUri() {
        $http = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'],'off')?'https':'http';
        if(isset($_SERVER['HTTP_HOST']))
            $host=$http.'://'.$_SERVER['HTTP_HOST'];

        if ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) // IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $requestUri = $_SERVER['REQUEST_URI'];
        }
        else
            $requestUri=false;

        return $host.$requestUri;
    }

}
if ( !function_exists( 'IsAjaxRequest' ) ) {
    function IsAjaxRequest() {
        return isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

if ( !function_exists( 'wpShowErrors' ) ) {
    function wpShowErrors() {
        error_reporting( E_ALL );
        ini_set( 'display_errors', 'On' );
        global $wpdb;
        $wpdb->show_errors();
    }
}


?>
