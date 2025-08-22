<?php
/**
 * Landing page
 * @since 0.5.0
 * @version 1.0.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div class='wrap'>
	<h1 class='wp-heading-inline'>Patrons Tips</h1>
	<hr class='wp-header-end'/>

	<?php
	// Check if there are tiers
	$tiers = patips_get_tiers_data();
	if( ! $tiers ) {
		patips_display_first_tier_notice();
		?><hr/><?php
	}
	?>

	<div id='patips-landing-container'>
		<div id='patips-landing-columns'>
			<div id='patips-comparison-container'>
				<div id='patips-comparison-intro'>
					<h3><?php esc_html_e( 'Make the most of Patrons Tips', 'patrons-tips' ); ?></h3>
					<p>
					<?php 
						echo sprintf(
							/* translators: %s = link to "Patrons Tips - Pro" sales page */
							esc_html__( 'Value your work and give it every opportunity with %s. Just give it a try, you have a 30-day money back guarantee.', 'patrons-tips' ), 
							patips_get_pro_sales_link() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
						);
					?>
					</p>
				</div>
				<?php
					$recurring_plugins = patips_get_recurring_payment_plugin_links();
				
					$features = array(
						array(
							'title'   => esc_html__( 'Create an unlimited number of tiers', 'patrons-tips' ),
							'tooltip' => esc_html__( 'Create as many tiers as you want, with their own characteristics (title, description, icon, price, restricted content...).', 'patrons-tips' )
						),
						array(
							'title'   => sprintf(
								/* translators: %s = link to "WooCommerce" download page */
								esc_html__( 'Sell one-off access to your tiers and receive online payments with %s', 'patrons-tips' ), 
								'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">'
									/* translators: This is a plugin name. */
									. esc_html__( 'WooCommerce', 'patrons-tips' )
								. '</a> <em>('
									. esc_html__( 'free', 'patrons-tips' )
								. ')</em>'
							),
							'tooltip' => esc_html__( 'Your patrons purchase the desired tier directly on your website.', 'patrons-tips' )
						),
						array(
							'title'   => esc_html__( 'Sell monthly subscriptions to your tiers and receive automatic recurring payments with:', 'patrons-tips' ),
							'tooltip' => esc_html__( 'Your patrons purchase a subscription to the desired tier and it is automatically renewed every month, the renewal payment is automatically triggered by the subscription plugin.', 'patrons-tips' ),
							'after'   => '<ul><li>' . implode( '<li>', $recurring_plugins ) . '</ul>'
						),
						array(

							'title'   => sprintf( 
								/* translators: %1$s = link to "WPML" download page. %2$s = link to "Polylang" download page. */
								esc_html__( 'Support multilanguage sites with %1$s and %2$s', 'patrons-tips' ),
								'<a href="https://wpml.org/purchase/" target="_blank">' . esc_html__( 'WPML', 'patrons-tips' ) . '</a>' . ' <em>(' . esc_html__( 'paid', 'patrons-tips' ) . ')</em>',
								'<a href="https://wordpress.org/plugins/polylang/" target="_blank">' . esc_html__( 'Polylang', 'patrons-tips' ) . '</a>' . ' <em>(' . esc_html__( 'free', 'patrons-tips' ) . ')</em>'
							),
							'tooltip' => esc_html__( 'Patrons Tips can be natively translated to all languages without additional plugins. WPML or Polylang only allow you to translate texts that you have manually entered like your tiers\' title and description.', 'patrons-tips' )
						),
						array(
							'title'   => esc_html__( 'Restrict posts, media, and products by tier to current patrons', 'patrons-tips' ),
							'tooltip' => esc_html__( 'Hide certain categories of posts, media or products to visitors, only your current patrons will be able to see them.', 'patrons-tips' ) . '<br/>' . esc_html__( 'You can define which tier can see which content categories.', 'patrons-tips' )
						),
						array(
							'title'   => esc_html__( 'Restrict posts, media, and products by tier to patrons of the publication month', 'patrons-tips' ),
							'tooltip' => esc_html__( 'Finally, a fair system for your patrons and you! E.g.: if you publish your work in October, then only the patrons of October can access it, for life. Starting November, new patrons won\'t be able to access it.', 'patrons-tips' ) . '<br/>' . esc_html__( 'You can define which tier can see which content categories.', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-restrict-period-content.png' ) . '" title="' . esc_html__( 'Restrict content per period', 'patrons-tips' ) . '" class="patips-demo-img patips-always-displayed" width="360">',
							'is_pro'  => 1
						),
						array(
							'title'   => esc_html__( 'Limit the number of patrons per tier per month', 'patrons-tips' ),
							'tooltip' => esc_html__( 'You can set a maximum number of contributors per tier per month. This is useful if a tier unlocks a limited reward (like a handmade item).', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-limited-tier.png' ) . '" title="' . esc_html__( 'Limited tier', 'patrons-tips' ) . '" class="patips-demo-img" width="260">',
							'is_pro'  => 1
						),
						array(
							'title'   => esc_html__( 'Display monthly stretch goals (number of patrons or amount of money)', 'patrons-tips' ),
							'tooltip' => esc_html__( 'Encourage more customers to contribute by unlocking rewards if a certain amount of money or a certain number of patrons is reached each month.', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-goals.png' ) . '" title="' . esc_html__( 'Monthly stretch goals', 'patrons-tips' ) . '" class="patips-demo-img" width="280">',
							'is_pro'  => 1
						),
						array(
							'title'   => esc_html__( 'Add and edit patrons\' patronage history from the backend', 'patrons-tips' ),
							'tooltip' => esc_html__( 'Manually change / revoke / subscribe a patron to any tier for the desired periods.', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-edit-patronage-history.png' ) . '" title="' . esc_html__( 'Edit patronage history', 'patrons-tips' ) . '" class="patips-demo-img" width="445">',
							'is_pro'  => 1
						),
						array(
							'title'   => esc_html__( 'Patrons can choose a nickname', 'patrons-tips' ),
							'tooltip' => esc_html__( 'Patrons can set their nickname from their account, it is instantly displayed in the thank you lists.', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-patron-nickname-and-badges.png' ) . '" title="' . esc_html__( 'Patron nickname and loyalty badges', 'patrons-tips' ) . '" class="patips-demo-img" width="320">',
							'is_pro'  => 1
						),
						array(
							'title'   => esc_html__( 'Automatically reward your patrons loyalty every year with a badge', 'patrons-tips' ),
							'tooltip' => esc_html__( 'A badge showing the patrons\' patronage number of years is automatically displayed next to their name in the thank you lists.', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-patron-nickname-and-badges.png' ) . '" title="' . esc_html__( 'Patron nickname and loyalty badges', 'patrons-tips' ) . '" class="patips-demo-img" width="320">',
							'is_pro'  => 1
						),
						array(
							'title'   => esc_html__( 'Monitor the sending of personalized loyalty rewards', 'patrons-tips' ),
							'tooltip' => esc_html__( 'If you send loyalty rewards to your patrons every year, you can know who you should send them to and when.', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-loyalty-reward-tracking.png' ) . '" title="' . esc_html__( 'Loyalty reward tracking', 'patrons-tips' ) . '" class="patips-demo-img" width="320">',
							'is_pro'  => 1
						),
						array(
							'title'   => esc_html__( 'Send automatic email reminders (with WooCommerce)', 'patrons-tips' ),
							'tooltip' => esc_html__( 'An email reminder is sent a few days before the patronage expires, so your patrons cannot forget to renew it.', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-email-reminder.png' ) . '" title="' . esc_html__( 'Email reminder', 'patrons-tips' ) . '" class="patips-demo-img" width="420">',
							'is_pro'  => 1
						),
						array(
							'title'   => esc_html__( 'Automatically sync all patrons (with WooCommerce)', 'patrons-tips' ),
							'tooltip' => esc_html__( 'Automatically add patrons to the list based on your WooCommerce orders and your tiers\' settings.', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-add-sync-new-patrons.png' ) . '" title="' . esc_html__( 'Add or sync new patrons', 'patrons-tips' ) . '" class="patips-demo-img" width="280">',
							'is_pro'  => 1
						),
						array(
							'title'   => esc_html__( 'Manually add a patron from the backend', 'patrons-tips' ),
							'tooltip' => esc_html__( 'Add a patron to the list with a click.', 'patrons-tips' ),
							'after'   => '<img src="' . esc_url( PATIPS_PLUGIN_URL . 'img/demo-add-sync-new-patrons.png' ) . '" title="' . esc_html__( 'Add or sync new patrons', 'patrons-tips' ) . '" class="patips-demo-img" width="280">',
							'is_pro'  => 1
						),

					);
				?>
				<table id='patips-comparison-table' class='patips-responsive-table'>
					<thead>
						<tr>
							<th data-column='feature'><?php esc_html_e( 'Features', 'patrons-tips' ); ?></th>
							<th data-column='free'><?php esc_html_e( 'Free', 'patrons-tips' ); ?></th>
							<th data-column='pro'><?php esc_html_e( 'Pro', 'patrons-tips' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php 
						foreach( $features as $feature ) { ?>
							<tr>
								<td data-column='feature' data-column-label='<?php esc_attr_e( 'Features', 'patrons-tips' ); ?>' class='<?php echo ! empty( $feature[ 'is_pro' ] ) ? 'patips-is-pro-feature' : '' ?>'>
									<span class='patips-feature-title'><?php echo wp_kses_post( $feature[ 'title' ] ); ?></span>
									<span class='patips-feature-tooltip'>
									<?php if( ! empty( $feature[ 'tooltip' ] ) ) { 
										patips_tooltip( $feature[ 'tooltip' ] );
									} ?>
									</span>
									<span class='patips-feature-after'>
									<?php if( ! empty( $feature[ 'after' ] ) ) { 
										echo wp_kses_post( $feature[ 'after' ] );
									} ?>
									</span>
								</td>
								<td data-column='free' data-column-label='<?php esc_attr_e( 'Free', 'patrons-tips' ); ?>'>
									<span class='dashicons <?php echo empty( $feature[ 'is_pro' ] ) ? 'dashicons-yes' : 'dashicons-no'; ?>'></span>
								</td>
								<td data-column='pro' data-column-label='<?php esc_attr_e( 'Pro', 'patrons-tips' ); ?>'>
									<span class='dashicons dashicons-yes'></span>
								</td>
							</tr>
						<?php }
					?>
					</tbody>
				</table>
			</div>

			<div id='patips-guarantees-container'>
				<div id='patips-purchase-pro-container'>
					<a href='<?php echo esc_url( patips_get_pro_sales_link( true ) ); ?>' target='_blank' class='patips-purchase-button'>
						<?php echo esc_html( apply_filters( 'patips_pro_purchase_button_label', _x( 'Purchase', 'verb', 'patrons-tips' ) ) ); ?>
					</a>
				</div>

				<div id='patips-guarantees-intro'>
					<h3><?php esc_html_e( 'Benefit from the best guarantees', 'patrons-tips' ); ?></h3>
					<p><?php esc_html_e( 'We are artists and content creators just like you. Your needs are ours, we created Patrons Tips to meet them, and we improve it with your feedback.', 'patrons-tips' ); ?></p>
					<p>
					<?php 
						echo sprintf(
							/* translators: %s = link to "Patrons Tips - Pro" sales page */
							esc_html__( '%s protects and values your work, and encourage patrons to contribute. So give it a try, if it doesn\'t meet your expectations, just let us know. That\'s why Patrons Tips is completely free and we offer a 30-day money back guarantee on the Pro version.', 'patrons-tips' ), 
							patips_get_pro_sales_link() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						);
					?>
					</p>
				</div>
				<div id='patips-guarantees'>
					<div class='patips-guarantee'>
						<div class='patips-guarantee-icon'><span class='dashicons dashicons-lock'></span></div>
						<h4><?php esc_html_e( 'Secure Payments', 'patrons-tips' ); ?></h4>
						<div class='patips-guarantee-description' ><?php esc_html_e( 'Online payments are secured by PayPal and Stripe', 'patrons-tips' ); ?></div>
					</div>
					<div class='patips-guarantee'>
						<div class='patips-guarantee-icon'><span class='dashicons dashicons-money'></span></div>
						<h4><?php esc_html_e( '30-Day money back guarantee', 'patrons-tips' ); ?></h4>
						<div class='patips-guarantee-description' ><?php esc_html_e( 'If you are not satisfied you will be 100% refunded', 'patrons-tips' ); ?></div>
					</div>
					<div class='patips-guarantee'>
						<div class='patips-guarantee-icon'><span class='dashicons dashicons-email-alt'></span></div>
						<h4><?php esc_html_e( 'Ready to help', 'patrons-tips' ); ?></h4>
						<div class='patips-guarantee-description' ><?php /* translators: %s = support email address) */ echo sprintf( esc_html__( 'Contact us at %s', 'patrons-tips' ), 'contact@patronstips.com' ); ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>