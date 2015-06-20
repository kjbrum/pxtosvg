<?php
/**
 * Shortcodes for displaying SVGs
 */
class PXtoSVG_Shortcodes {
    function __construct() {
        /**
         * Add the SVG shortcode
         */
        add_shortcode( 'svg', array( $this, 'display_svg' ) );
    }

    /**
     * Print an SVG file
     *
     * @param   array   $atts     Attributes
     * @param   string  $content  Content between the brackets
     * @return  string  $svg      SVG HTML
     */
    function display_svg( $atts, $content ) {
        // Extract the attributes
        extract( shortcode_atts( array(
            'filename' => '',
            'color'    => '',
            'width'    => '',
            'height'   => ''
        ), $atts ) );

        // Get the uploads directory
        $uploads_dir = wp_upload_dir();

        // Get the needed SVG
        $svg = file_get_contents( $uploads_dir['basedir'].'/svg/'.$filename.'.svg' );

        return $svg;
    }
}

new PXtoSVG_Shortcodes();