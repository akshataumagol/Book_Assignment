<?php
/**
 * Template: Single Book

 */

if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();

    $author = get_post_meta( get_the_ID(), '_bm_author', true );
    $genre  = get_post_meta( get_the_ID(), '_bm_genre',  true );
    $date   = get_post_meta( get_the_ID(), '_bm_date',   true );

    if ( $date ) {
        $date = date_i18n( get_option( 'date_format' ), strtotime( $date ) );
    }
    ?>

    <div class="bm-single-wrapper">

        <a href="javascript:history.back()" class="bm-back-link">← Back to Library</a>

        <article class="bm-single-card">

            <div class="bm-single-hero">
                <span class="bm-single-icon">📖</span>
                <?php if ( $genre ) : ?>
                    <span class="bm-badge bm-badge-lg"><?php echo esc_html( $genre ); ?></span>
                <?php endif; ?>
            </div>

            <div class="bm-single-body">

                <h1 class="bm-single-title"><?php the_title(); ?></h1>

                <div class="bm-single-meta">
                    <?php if ( $author ) : ?>
                        <div class="bm-meta-item">
                            <span class="bm-meta-label">✍️ Author</span>
                            <span class="bm-meta-value"><?php echo esc_html( $author ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $genre ) : ?>
                        <div class="bm-meta-item">
                            <span class="bm-meta-label">🏷️ Genre</span>
                            <span class="bm-meta-value"><?php echo esc_html( $genre ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $date ) : ?>
                        <div class="bm-meta-item">
                            <span class="bm-meta-label">📅 Published</span>
                            <span class="bm-meta-value"><?php echo esc_html( $date ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bm-single-description">
                    <h2>Description</h2>
                    <div class="bm-description-content">
                        <?php the_content(); ?>
                    </div>
                </div>

            </div><!-- .bm-single-body -->

        </article>

    </div><!-- .bm-single-wrapper -->

    <?php
endwhile;

get_footer();
