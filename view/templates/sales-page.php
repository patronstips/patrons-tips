<?php
/**
 * Patronage sales page
 * @since 0.20.0
 * @version 0.26.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

$is_block = use_block_editor_for_post_type( 'page' );
?>

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Here is my dream', 'patrons-tips' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( 'Say <strong>hello</strong> and <strong>introduce yourself</strong> in one concise sentence.', 'patrons-tips' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( 'Describe <strong>what you do</strong> in another concise sentence.', 'patrons-tips' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( '<strong>Show</strong> your <strong>biggest achievement</strong> if you have one, but don\'t worry if you don\'t have any yet :)', 'patrons-tips' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( 'Explain <strong>your long term project</strong>, straight to the point, with simple words and <strong>illustrations</strong>.', 'patrons-tips' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( 'Conclude: you need <strong>financial support</strong> to carry it out, hence the patronage system.', 'patrons-tips' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo wp_kses_post( __( 'It will come true thanks to you', 'patrons-tips' ) ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( 'Explain the <strong>patronage system</strong> with your own words, in a concise way.', 'patrons-tips' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><small><em><?php echo wp_kses_post( __( 'Emphasize that even small one-off contributions are worth it, and the bigger the contribution, the bigger the reward.', 'patrons-tips' ) ); ?></em></small></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo wp_kses_post( __( 'You will be pampered in the process', 'patrons-tips' ) ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( 'Make a beautiful <strong>infographic</strong> to show the <strong>tiers</strong> and their corresponding <strong>rewards</strong>.', 'patrons-tips' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><small><em><?php echo wp_kses_post( __( 'It must be very clear what the tiers\' name are and what their rewards are. Use both images and keywords to describe them.', 'patrons-tips' ) ); ?></em></small></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo wp_kses_post( __( 'Looks great, right? Then, let\'s go!', 'patrons-tips' ) ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( 'Briefly explain <strong>when</strong> the rewards will be unlocked / sent, and <strong>where</strong> to find them.', 'patrons-tips' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><small><em><?php echo wp_kses_post( __( 'You can illustrate it with a screenshot of the patron area for example.', 'patrons-tips' ) ); ?></em></small></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><small><em><?php echo wp_kses_post( sprintf(
	/* translators: %1$s = "Patron post list". %2$s = "[patronstips_patron_posts]" */
	__( 'You can also show a list of locked content to make customers\' mouths water with the "%1$s" block or the %2$s shortcode, as below:', 'patrons-tips' ),
	'<strong>' . __( 'Patron post list', 'patrons-tips' ) . '</strong>',
	'<code>[[patronstips_patron_posts]]</code>' ) );
?></em></small></p>
<!-- /wp:paragraph -->

<?php if( $is_block ) { ?>
<!-- wp:patrons-tips/patron-post-list {"types":["post","page","attachment","product"],"restricted":1,"perPage":3} /-->
<?php } else { ?>
<!-- wp:shortcode -->
[patronstips_patron_posts types="post,page,attachment,product" restricted="1" per_page="3"]
<!-- /wp:shortcode -->
<?php } ?>

<!-- wp:paragraph -->
<p><small><em><?php /* translators: %s = link to 'Patrons Tips - Pro' sales page */ echo wp_kses_post( sprintf( __( 'If you are using %s, you can unlock content related to the month of the contribution. Let your customers know they need to contribute before the last day of the month to unlock it.', 'patrons-tips' ), patips_get_pro_sales_link() ) ); ?></em></small></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( 'Encourage customers to choose a tier and <strong>get started</strong>!', 'patrons-tips' ) ); ?></p>
<!-- /wp:paragraph -->

<?php if( ! patips_is_block_theme() ) { ?>
	<!-- wp:paragraph -->
	<p><small><em><?php echo wp_kses_post( sprintf( 
		/* translators: %1$s = "Patronage form". %2$s = "[patronstips_tier_form]" */
		__( 'Add the "%1$s" block or the %2$s shortcode in this page or in its sidebar to allow customers to purchase a tier, as below:', 'patrons-tips' ), 
		'<strong>' . __( 'Patronage form', 'patrons-tips' ) . '</strong>', 
		'<code>[[patronstips_tier_form]]</code>' ) ); 
	?></em></small></p>
	<!-- /wp:paragraph -->

	<?php if( $is_block ) { ?>
	<!-- wp:patrons-tips/tier-form /-->
	<?php } else { ?>
	<!-- wp:shortcode -->
	[patronstips_tier_form]
	<!-- /wp:shortcode -->
	<?php } ?>
<?php } else { ?>
	<!-- wp:paragraph -->
	<p><small><em><?php echo wp_kses_post( sprintf( 
		/* translators: %s = Path to sales page template in Full Site Editor: "Appearence > Editor > Templates > Patrons Tips > Page: Patronage sales" (with a link). */
		__( 'The subscription form is located in the sidebar, along with other useful blocks, you can customize them in %s.', 'patrons-tips' ), 
		'<a href="' . esc_url( admin_url( '/site-editor.php?p=%2Ftemplate&activeView=Patrons+Tips' ) ) . '" target="_blank"><strong>'
			/* translators: %s = Template title "Page: Patronage sales" */
			. sprintf( __( 'Appearence > Editor > Templates > Patrons Tips > %s', 'patrons-tips' ), __( 'Page: Patronage sales', 'patrons-tips' ) )
		. '</strong></a>' ) );
	?></em></small></p>
	<!-- /wp:paragraph -->
<?php
}