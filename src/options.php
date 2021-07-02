<?php

namespace SearchWPStatic;

class Options {

	public $slug = 'search_wp_static';

	public function __construct() {
    	// Hook into the admin menu
	    add_action( 'admin_menu', [ $this, 'create_plugin_settings_page' ] );

	    // Add Settings and Fields
    	add_action( 'admin_init', array( $this, 'setup_sections' ) );
    	add_action( 'admin_init', array( $this, 'setup_fields' ) );

	}

	public function create_plugin_settings_page() {
	    $page_title = 'SearchWP Static';
	    $menu_title = 'SearchWP Static';
	    $capability = 'manage_options';
	    $callback = [ $this, 'plugin_settings_page_content' ];
	    $icon = 'dashicons-admin-plugins';
	    $position = 100;

	    add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $this->slug, $callback );
	}

	public function plugin_settings_page_content() {?>
    	<div class="wrap">
    		<h2>Search WP Static Settings</h2>
    		<form method="POST" action="options.php">
                <?php
                    settings_fields( $this->slug );
                    do_settings_sections( $this->slug );
                    submit_button();
                ?>
    		</form>
    	</div> <?php
    }

	public function setup_sections() {
        add_settings_section( 'main_section', 'Main settings', array( $this, 'section_callback' ), $this->slug );
    }

 	public function section_callback( $arguments ) {
    	switch( $arguments['id'] ){
    		case 'main_section':
    			break;
    	}
    }

    public function setup_fields() {
        $fields = [
        	[
        		'uid' => 'search_wp_static_urls',
        		'label' => 'Static URLs to index',
        		'section' => 'main_section',
        		'type' => 'textarea',
                'helper' => '<br>A list of absolute URLs to index, separated by newline. For each URL, we will make a new dummy page.<br>The dummy page will redirect to the specified URL. Note: only URLs on this site can be indexed.',
        	],
        ];

    	foreach( $fields as $field ){
        	add_settings_field( $field['uid'], $field['label'], [ $this, 'field_callback' ], $this->slug, $field['section'], $field );
            register_setting( $this->slug, $field['uid'] );
    	}
    }

	public function field_callback( $arguments ) {
        $value = get_option( $arguments['uid'] );

        if( ! $value ) {
            $value = $arguments['default'];
        }

        switch( $arguments['type'] ){
            case 'text':
            case 'password':
            case 'number':
                printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" style="width:300px" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
                break;
            case 'textarea':
                printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
                break;
            case 'select':
            case 'multiselect':
                if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                    $attributes = '';
                    $options_markup = '';
                    foreach( $arguments['options'] as $key => $label ){
                        $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value[ array_search( $key, $value, true ) ], $key, false ), $label );
                    }
                    if( $arguments['type'] === 'multiselect' ){
                        $attributes = ' multiple="multiple" ';
                    }
                    printf( '<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup );
                }
                break;
            case 'radio':
            case 'checkbox':
                if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                    $options_markup = '';
                    $iterator = 0;
                    foreach( $arguments['options'] as $key => $label ){
                        $iterator++;
                        $options_markup .= sprintf( '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked( $value[ array_search( $key, $value, true ) ], $key, false ), $label, $iterator );
                    }
                    printf( '<fieldset>%s</fieldset>', $options_markup );
                }
                break;
        }

        if( $helper = $arguments['helper'] ){
            printf( '<span class="helper"> %s</span>', $helper );
        }

        if( $supplimental = $arguments['supplimental'] ){
            printf( '<p class="description">%s</p>', $supplimental );
        }

    }

}
