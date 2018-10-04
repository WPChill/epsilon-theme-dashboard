<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Epsilon_Widgets_Importer
 */
class Epsilon_Widgets_Importer extends Epsilon_Importer {
	/**
	 * @return string
	 */
	public function import() {
		global $wp_registered_sidebars;
		foreach ( $this->content['content'] as $sidebar => $widgets ) {
			foreach ( $widgets as $widget => $props ) {
				$widget_type = preg_replace( '/-[0-9]+$/', '', $widget );
				$widget_id   = str_replace( $widget_type . '-', '', $widget );

				$prop = array(
					'available'            => false,
					'sidebar_id'           => 'wp_inactive_widgets',
					'sidebar_message_type' => 'error',
				);

				if ( isset( $wp_registered_sidebars[ $sidebar ] ) ) {
					$prop['available']            = true;
					$prop['sidebar_id']           = $sidebar;
					$prop['sidebar_message_type'] = 'success';
				}

				$temp = array(
					'_multiwidget' => 1,
				);

				$widget_instance   = get_option( 'widget_' . $widget_type );
				$widget_instance   = ! empty( $widget_instance ) ? $widget_instance : $temp;
				$widget_instance[] = $this->content['content'][ $sidebar ][ $widget ];

				// Get the key it was given.
				end( $widget_instance );
				$new_id = key( $widget_instance );
				if ( '0' === strval( $new_id ) ) {
					$new_id                     = 1;
					$widget_instance[ $new_id ] = $widget_instance[0];
					unset( $widget_instance[0] );
				}
				if ( isset( $widget_instance['_multiwidget'] ) ) {
					$multiwidget = $widget_instance['_multiwidget'];
					unset( $widget_instance['_multiwidget'] );
					$widget_instance['_multiwidget'] = $multiwidget;
				}

				// Update option with new widget.
				update_option( 'widget_' . $widget_type, $widget_instance );
				$sidebars_widgets = get_option( 'sidebars_widgets' );
				if ( ! $sidebars_widgets ) {
					$sidebars_widgets = array();
				}
				$new_instance_id = $widget_type . '-' . $new_id;

				// Add new instance to sidebar.
				$sidebars_widgets[ $prop['sidebar_id'] ][] = $new_instance_id;

				// Save the amended data.
				update_option( 'sidebars_widgets', $sidebars_widgets );
			}

		}// End foreach().
		return 'ok';
	}
}
