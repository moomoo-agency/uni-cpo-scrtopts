<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uni_Cpo_Scrtopts Class
 */
final class Uni_Cpo_Scrtopts {

	public $version = '1.0.0';

	protected static $_instance = null;

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'uni-cpo-scrtopts' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'uni-cpo-scrtopts' ), '1.0.0' );
	}

	/**
	 * Main Uni_Cpo_Scrtopts Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Uni_Cpo_Scrtopts Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 *  Init hooks
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

		add_action( 'woocommerce_product_options_pricing', array( $this, 'display_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_fields' ) );

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
	}

	function load_scripts() {
		if ( is_singular( 'product' ) ) {
			global $post;

			$scrtOptsData = [];
			$pairs_to_hide = get_post_meta( $post->ID, 'uni_cpo_scrtopts_pairs_to_hide', true );
			$pairs_to_disable = get_post_meta( $post->ID, 'uni_cpo_scrtopts_pairs_to_disable', true );

			if ( ! empty( $pairs_to_hide ) || ! empty( $pairs_to_disable ) ) {
				$pairs_set_hide = explode( ';', $pairs_to_hide );
				$pairs_set_disable = explode( ';', $pairs_to_disable );

				wp_enqueue_style(
					'uni-cpo-scrtopts-styles',
					$this->plugin_url() . '/assets/css/frontend.css',
					array(),
					$this->version
				);

				if ( ! empty( $pairs_to_hide ) && ! empty( $pairs_set_hide ) ) {
					$custom_css   = [];

					foreach ( $pairs_set_hide as $set ) {
						$pairs          = explode( ':', $set );

						if (!empty($pairs[0]) && !empty($pairs[1])) {
							$input          = $pairs[0];
							$custom_css[]   = "#{$input} {display: none;}";
							//$notice         = $pairs[1];
							$scrtOptsData[] = [
								'input'  => $input,
								'notice' => $pairs[1]
							];
						}
					}

					wp_add_inline_style( 'uni-cpo-scrtopts-styles', implode( ' ', $custom_css ) );
				}

				wp_register_script(
					'uni-cpo-scrtopts-script',
					$this->plugin_url() . '/assets/js/frontend.js',
					array( 'jquery', 'uni-cpo-frontend' ),
					$this->version,
					true
				);

				wp_enqueue_script( 'uni-cpo-scrtopts-script' );

				if ( ! empty( $pairs_to_disable ) && ! empty( $pairs_set_disable ) ) {
					$inline_js   = [];
					$custom_css = [];

					foreach ( $pairs_set_disable as $set ) {
						$pairs          = explode( ':', $set );

						if (!empty($pairs[0]) && !empty($pairs[1])) {
							$input          = $pairs[0];
							$inline_js[]    = "var scropts_{$input} = document.querySelector('[name={$input}]'); if (scropts_{$input}) { var scropts_{$input}_cloned = scropts_{$input}.cloneNode(true); scropts_{$input}_cloned.type = 'hidden'; scropts_{$input}_cloned.id = scropts_{$input}.id + '-cloned'; scropts_{$input}.parentNode.insertBefore(scropts_{$input}_cloned, scropts_{$input}); } jQuery(scropts_{$input}).attr('disabled', true);";
							$notice         = $pairs[1];
							$custom_css[]   = "#{$notice} {display: none;}";
							$scrtOptsData[] = [
								'input'  => $input,
								'notice' => $pairs[1]
							];
						}
					}

					wp_add_inline_script( 'uni-cpo-scrtopts-script', implode( ' ', $inline_js ) );
					wp_add_inline_style( 'uni-cpo-scrtopts-styles', implode( ' ', $custom_css ) );
				}

				wp_localize_script( 'uni-cpo-scrtopts-script', 'scrtOptsData', $scrtOptsData );
			}
		}
	}

	/**
	 * Display product settings fields
	 */
	public function display_fields() {
		woocommerce_wp_text_input(
			array(
				'id'          => 'uni_cpo_scrtopts_pairs_to_hide',
				'label'       => __( 'Set  pairs of input <-> dynamic notice to hide inputs', 'uni-cpo-scrtopts' ),
				'description' => __( 'Input goes first! Example: uni_cpo_inp1:uni_cpo_notice1;uni_cpo_other_input:uni_cpo_notice_special', 'uni-cpo-scrtopts' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => 'uni_cpo_scrtopts_pairs_to_disable',
				'label'       => __( 'Set  pairs of input <-> dynamic notice to disable inputs, but leave them visible', 'uni-cpo-scrtopts' ),
				'description' => __( 'Input goes first! Example: uni_cpo_inp1:uni_cpo_notice1;uni_cpo_other_input:uni_cpo_notice_special', 'uni-cpo-scrtopts' ),
			)
		);
	}

	/**
	 * Save the custom fields using CRUD method
	 *
	 * @param $post_id
	 */
	public function save_fields( $post_id ) {

		$product = wc_get_product( $post_id );
		$product->update_meta_data( 'uni_cpo_scrtopts_pairs_to_hide', sanitize_text_field( $_POST['uni_cpo_scrtopts_pairs_to_hide'] ) );
		$product->update_meta_data( 'uni_cpo_scrtopts_pairs_to_disable', sanitize_text_field( $_POST['uni_cpo_scrtopts_pairs_to_disable'] ) );

		$product->save();

	}

	/**
	 * load_plugin_textdomain()
	 */
	public function load_plugin_textdomain() {
		load_textdomain( 'uni-cpo-scrtopts', WP_LANG_DIR . '/uni-cpo-scrtopts/uni-cpo-scrtopts-' . get_locale() . '.mo' );
		load_plugin_textdomain( 'uni-cpo-scrtopts', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
	}

	/**
	 * plugin_url()
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * plugin_path()
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get Ajax URL.
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * on plugin activation
	 */
	public function activation() {
	}

	/**
	 * on plugin activation
	 */
	public function deactivation() {
	}
}
