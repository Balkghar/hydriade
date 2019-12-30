<?php
class wp_hydriade_shortcode{
    public function __construct(){
        add_action('init', array($this,'register_hydriade_shortcodes')); //shortcodes
        add_action('addRole', array($this,'add_role_hydriadeMJ'));
        add_action('init', array($this,'partie_add'));
    }
    public function register_hydriade_shortcodes(){
        add_shortcode('wp_parties', array($this,'show_parties'));
    }
    public function show_parties(){
        $Languages = array(
            "french" => "Français",
            "english" => "Anglais",
            "deutsch" => "Allemand",
        );
        if(is_user_logged_in())
        {
            /**Permet d'obtenir la taxonomy qu'on a créé avant */
            $terms = get_terms(array(
                'taxonomy' => 'types',
                'hide_empty' => false,
            ));
            /**Vérifie si le tableau n'est pas vide */
            if(!empty($terms)){
                /**Boucle pour afficher les éléments du tableau */
                foreach($terms as $term){
                    /**Reset du query */
                    wp_reset_query();
                    /**Argument pour chercher les parties selon la taxonomy */
                    $args = array('post_type' => 'wp_parties',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'types',
                                'field' => 'slug',
                                'terms' => $term->slug,
                            ),
                        ),
                    );

                    /**Affichage des informations pour les parties */
                    $loop = new WP_Query($args);
                    if($loop->have_posts()) {
                        $html .= '<h2>'.$term->name.'</h2><div class="row">';

                        while($loop->have_posts()) : $loop->the_post();
                            $html .= '<div class="column"><div class="card"><h3>'.get_the_title().'</h3><p><B>Univers de jeu : </B>'.get_post_meta(get_the_ID(),'wp_party_univers', true).'</p><p><B>Ambiance : </B>'.get_post_meta(get_the_ID(),'wp_party_ambiance', true).'</p><p><B>MJ : </B>'.get_post_meta(get_the_ID(),'wp_party_GM', true).'</p><p><B>Nombre de joueurs : </B>'.get_post_meta(get_the_ID(),'wp_party_players', true).'</p><p><B>Temps estimé : </B>'.get_post_meta(get_the_ID(),'wp_party_time', true).'</p><p><B>Langue : </B>'.get_post_meta(get_the_ID(),'wp_party_language', true).'</p><div class="pitch"><button onclick="showOrHide('.get_the_ID().')">Pitch du scénario<b>+</b></button><div id="'.get_the_ID().'" class="displayNone"><p>'.get_post_meta(get_the_ID(),'wp_party_pitch', true).'</p></div></div></div></div>';
                        endwhile;
                    }
                    else{
                        $html .= "Pas encore de parties...";
                    }
                    $html .= '<div class="column"><div class="card"><button onclick="showOrHide('.$term->term_id.')"><h3><b>+Ajouter une partie+</b></h3></button><div id="'.$term->term_id.'" class="displayNone">
                    <form enctype="multipart/form-data" action="" name="new_post" id="new_post" method="post">
                    Titre de la partie :<br>
                    <input type="text" name="title">
                    Univers du jeu :<br>
                    <input type="text" name="universe">
                    Ambiance :<br>
                    <input type="text" name="ambiance">
                    Maître de jeu :<br>
                    <input type="text" name="MJ">
                    Nombre de joueurs :<br>
                    <select name="players">';
                    for($i = 1; $i <= 10; $i++){
                        
                            $html .='<option value="'.$i.'">'.$i.'</option>';
                        
                    }
                    $html .= '</select>
                    Temps estimé :<br>
                    <select name="time">';
                    for($i = 1; $i <= 10; $i++){
                        
                            $html .='<option value="'.$i.'">'.$i.'</option>';
                        
                    }
                    $html .= '</select>
                    Langue :<br>
                    <select id="wp_party_language" name="language">';
                    foreach ($Languages as $Langue){
                        $html .= '<option value="'.$Langue.'">'.$Langue.'</option>';
                    }
                    $html .=  '</select>';
                    $html .= 'Pitch du scénario :<br>
                    <textarea name="pitch"></textarea>
                    <input name="category" type="hidden" value="'.$term->term_id.'">
                    <input type="submit" value="Envoyer">
                    </form>
                    </div></div></div></div>';
                    /**Fin de la boucle */
                }
            }
            wp_reset_postdata();
        }
        else{
            $html .="<h2>Vous devez vous connecter pour pouvoir voir les parties des hydriades</h2>";
        }
        return $html;
    }
    public function partie_add(){
        if(!empty($_POST['title']) && !empty($_POST['universe']) && !empty($_POST['ambiance']) && !empty($_POST['MJ']) && !empty($_POST['players']) && !empty($_POST['language']) && !empty($_POST['pitch']) && !empty($_POST['category'])  && !empty($_POST['time'])){
            $post_id = wp_insert_post(array(
                'post_title' => esc_attr(strip_tags($_POST['title'])),
				'post_type' => 'wp_parties',
                'post_status' => 'pending'
            ));
            $term = get_term(esc_attr(strip_tags($_POST['category'])) , 'types');
            wp_set_object_terms($post_id, $term->name, 'types');

            update_post_meta($post_id, 'wp_party_GM', esc_attr(strip_tags($_POST['MJ'])));
            update_post_meta($post_id, 'wp_party_ambiance', esc_attr(strip_tags($_POST['ambiance'])));
            update_post_meta($post_id, 'wp_party_univers', esc_attr(strip_tags($_POST['universe'])));
            update_post_meta($post_id, 'wp_party_pitch', esc_attr(strip_tags($_POST['pitch'])));
            update_post_meta($post_id, 'wp_party_language', esc_attr(strip_tags($_POST['language'])));
            update_post_meta($post_id, 'wp_party_time', esc_attr(strip_tags($_POST['time'])));
            update_post_meta($post_id, 'wp_party_players', esc_attr(strip_tags($_POST['players'])));
        }
        else{

        }
    }
    public function add_role_hydriadeMJ($ticket){
        add_user_meta(get_current_user_id(),"RoleHydriade", "MJ");
    }
}
$wp_hydriade_shortcode = new wp_hydriade_shortcode;

?>