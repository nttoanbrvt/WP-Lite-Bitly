<?php
/*
Plugin Name:    WP Lite Bitly
Plugin URI:     http://phpface.net
Description:    Another Wordpress plugin for converting WP permalink to bitly shortlink
Version:        1.0
Author:         Toan Nguyen
Author URI:     http://phpface.net
Text Domain:	wp-lite-bitly
License:		GPLv3
License URI:	http://www.gnu.org/licenses/gpl-3.0.html
*/ 
if( ! class_exists( 'WP_Lite_Bitly' ) ){

	class WP_Lite_Bitly {

		/**
		 * Holds the bitly API url
		 * @var url
		 */
		public $apiurl  =   'https://api-ssl.bitly.com/v3/shorten';
		 
		function __construct() {
			
			add_action( 'admin_enqueue_scripts' , array( $this , 'wp_enqueue_style' ) );
			 
			add_action( 'wp_insert_post' , array( $this , 'wp_insert_post' ), 10, 3 );
			add_action( 'edit_form_before_permalink' , array( $this , 'edit_form_before_permalink' ),10, 1 );
			add_filter( 'post_link' , array( $this , 'post_link' ), 10, 3 );
			
			add_action( 'customize_register', array( $this , 'customize_register' ) );
			 
		}
		
		function wp_enqueue_style(){
			wp_enqueue_style( 'wp-lite-bitly-style', plugin_dir_url( __FILE__ ) . 'style.css', array(), '1.0' );
		}
		 
		/**
		 * Do bitly shorten
		 * @param url $longUrl
		 * @return bitly url or original $permalink
		 */
		 
		function shorten( $longUrl ) {
			
			$access_token	=	get_option( 'wp-lite-bitly-access-token' );
			
			if( empty( $access_token ) ){
				return $longUrl;
			}

			// Build the api url
			$apiurl =   add_query_arg( array(
				'access_token'  =>  $access_token,
				'longUrl'       =>  $longUrl
			), $this->apiurl );
			 
			// Call api
			$response   =   wp_remote_get( $apiurl );

			// Return $permalink if WP_Error was returned.
			if( is_wp_error( $response ) ){
				//error_log( print_r( $response ) ); // for debugging purpose.
				return $response;
			}
			 
			// Parse the response body
			$response   =   json_decode( wp_remote_retrieve_body( $response ), true );
			 
			return isset( $response['data']['url'] ) ? $response['data']['url'] : $longUrl;
		}
		 
		/**
		 * Fires once a post has been saved.
		 *
		 * @param int     $post_ID Post ID.
		 * @param WP_Post $post    Post object.
		 * @param bool    $update  Whether this is an existing post being updated or not.
		 */
		 
		function wp_insert_post( $post_ID, $post, $update ) {

			$shortUrl   =   $this->shorten( get_permalink( $post_ID ) );

			// Always update the shorturl into post meta
			if( ! is_wp_error( $shortUrl ) && ! empty( $shortUrl ) ){
				return update_post_meta( $post_ID , '__bitly_url', $shortUrl );
			}
		}
		
		/**
		 * 
		 * @param unknown_type $post
		 */
		function edit_form_before_permalink( $post ){
			
			$maybe_bitly_url	=	get_post_meta( $post->ID, '__bitly_url', true );
			
			if( ! empty( $maybe_bitly_url ) ){
				
				?>
				
					<div class="inside">
						<div id="shortlink-box" class="hide-if-no-js">
							<strong><?php esc_html_e( 'Shortlink:', 'wp-lite-bitly' )?></strong>
							<a target="_blank" href="<?php echo esc_url( $maybe_bitly_url );?>"><?php echo $maybe_bitly_url;?></a>
						</div>
					</div>
					
				<?php 
				
			}
		}
		 
		/**
		 * Filters the permalink for a post.
		 *
		 * Only applies to posts with post_type of 'post'.
		 * @param string  $permalink The post's permalink.
		 * @param WP_Post $post      The post in question.
		 * @param bool    $leavename Whether to keep the post name.
		 */
		 
		function post_link( $permalink, $post, $leavename ) {
			
			if( is_admin() ){
				return $permalink;
			}
			
			$maybe_bitly_url	=	get_post_meta( $post->ID, '__bitly_url', true );
			 
			if( ! empty( $maybe_bitly_url ) ){
				return $maybe_bitly_url;
			}
			 
			return $permalink;
		}
		
		/**
		 * WP Lite Bitly Customizer
		 * @param $wp_customize
		 */
		function customize_register( $wp_customize  ){
			
			$wp_customize->add_section( 'wp-lite-bitly-section' , array(
				'title'      => esc_html__( 'WP Lite Bitly', 'wp-lite-bitly' ),
				'priority'   => 50,
			) );
			
			$wp_customize->add_setting( 'wp-lite-bitly-access-token',
				array(
					'default' 		=> '',
					'type' 			=> 'option',
					'capability' 	=> 'edit_theme_options',
					'transport' 	=> 'postMessage'
				)
			);	

			$wp_customize->add_control(
				'wp-lite-bitly-section-access-token',
				array(
					'label'    		=> esc_html__( 'Access Token', 'wp-lite-bitly' ),
					'section'  		=> 'wp-lite-bitly-section',
					'settings' 		=> 'wp-lite-bitly-access-token',
					'type'     		=> 'text',
					'description'	=>	esc_html__( 'Enter the bitly access token here', 'wp-lite-bitly' )
				)
			);			
		}

	}
	 
	$WP_Lite_Bitly	=	new WP_Lite_Bitly();
}