<?php
class wp_hydriade_shortcode{
    public function __construct(){
        add_action('init', array($this,'register_hydriade_shortcodes')); //shortcodes
    }
    public function register_hydriade_shortcodes(){
        add_shortcode('wp_parties', array($this,'show_parties'));
    }
    public function show_parties(){
        
        $terms = get_terms(array(
            'taxonomy' => 'types',
            'hide_empty' => false,
        ));
        if(!empty($terms)){
            foreach($terms as $term){
                /*$html .= '<h2>'.$term->name.'</h2>';*/
                
                wp_reset_query();
                $args = array('post_type' => 'wp_parties',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'types',
                            'field' => 'slug',
                            'terms' => $term->slug,
                        ),
                    ),
                );

                $loop = new WP_Query($args);
                if($loop->have_posts()) {
                    $html .= '<h2>'.$term->name.'</h2><div class="row">';

                    while($loop->have_posts()) : $loop->the_post();
                        $html .= '<div class="column"><div class="card"><h3>'.get_the_title().'</h3><p><B>Univers de jeu : </B>'.get_post_meta(get_the_ID(),'wp_party_univers', true).'</p><p><B>Ambiance : </B>'.get_post_meta(get_the_ID(),'wp_party_ambiance', true).'</p><p><B>MJ : </B>'.get_post_meta(get_the_ID(),'wp_party_GM', true).'</p><p><B>Nombre de joueurs : </B>'.get_post_meta(get_the_ID(),'wp_party_players', true).'</p><p><B>Temps estimé : </B>'.get_post_meta(get_the_ID(),'wp_party_time', true).'</p><p><B>Langue : </B>'.get_post_meta(get_the_ID(),'wp_party_language', true).'</p><div class="pitch"><button onclick="showOrHide('.get_the_ID().')">Pitch du scénario <b>+</b></button><div id="'.get_the_ID().'" class="displayNone"><p>'.get_post_meta(get_the_ID(),'wp_party_pitch', true).'</p></div></div></div></div>';
                    endwhile;
                }
                else{
                    $html .= "Pas encore de parties...";
                }
                $html .= '</div>';
                /*$args = array(
                    'post_type' => 'wp_parties',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'types',   // taxonomy name
                            'field' => 'term_id',           // term_id, slug or name
                            'terms' => $term->term_id, // term id, term slug or term name
                        )
                    )
                );

                $query = new WP_Query($args);

                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                 
                        $query->the_post();
                        // Post data goes here.
                    }
                }
                else{
                    $html .= 'Pas encore de topic !';
                }*/
            }
        }
        return $html;
        wp_reset_postdata();
    }
}
$wp_hydriade_shortcode = new wp_hydriade_shortcode;

?>