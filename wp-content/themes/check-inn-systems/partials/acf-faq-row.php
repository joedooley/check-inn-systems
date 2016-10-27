<?php
/**
 * Default code for a ACF FAQ
 *
 * @package    Check Inn Systems
 */


$add_heading = get_sub_field('add_section_heading');
$content = get_sub_field('content'); ?>

<section class="row  <?php the_sub_field('css_class'); ?>">
    <div class="wrap">

        <?php

        if ($add_heading) {
            the_sub_field('section_heading');
        }

        if ( $content ) {
            echo '<div class="left-content">' . $content . '</div>';
        }

        echo '<div class="right-content">';

        echo '<div id="accordion">';

                if ( have_rows( 'accordion' ) ) {

                    while (have_rows('accordion')) : the_row();

                        echo '<div class = "accordion-item">';


                        $heading = get_sub_field('header');
                        $content = get_sub_field('hidden_content');

                        if ($heading) {
                            echo '<h2 class = "accordion-heading heading">' . $heading . '</h2>';
                        }

                        if ($content) {
                            echo '<div class = "accordion-content">' . $content . '</div>';
                        }

                        echo '</div>';

                    endwhile;
                }

        echo '</div>';

        echo '</div>';


        ?>

    </div>
</section>

