<?php
class wp_hydriade_shortcode{
    public function __construct(){
        add_action('init', array($this,'register_hydriade_shortcodes')); //shortcodes
        add_action('init', array($this,'partie_add'));
        add_action('init', array($this,'plOrGmAdd'));
    }
    public function register_hydriade_shortcodes(){
        add_shortcode('wp_parties', array($this,'show_parties'));
    }
    /**
     * Fonction permettant de montrer les parties aux clients connectés
     * 
     */
    public function show_parties(){
        $Languages = array(
            "french" => "Français",
            "english" => "Anglais",
            "deutsch" => "Allemand"
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
                    $html .= '<h2>'.$term->name.'</h2><div class="row">';
                    if($loop->have_posts()) {
                        while($loop->have_posts()) : $loop->the_post();
                            $html .= '<div class="column"><div class="card"><h3>'.get_the_title().'</h3><p><B>Univers de jeu : </B>'.get_post_meta(get_the_ID(),'wp_party_univers', true).'</p><p><B>Ambiance : </B>'.get_post_meta(get_the_ID(),'wp_party_ambiance', true).'</p><p><B>MJ : </B>'.get_post_meta(get_the_ID(),'wp_party_GM', true).'</p><p><B>Nombre de joueurs : </B>'.get_post_meta(get_the_ID(),'wp_party_players', true).'</p><p><B>Temps estimé : </B>'.get_post_meta(get_the_ID(),'wp_party_time', true).'</p><p><B>Langue : </B>'.get_post_meta(get_the_ID(),'wp_party_language', true).'</p><div class="pitch"><button onclick="showOrHide('.get_the_ID().')">Pitch du scénario<b>+</b></button><div id="'.get_the_ID().'" class="displayNone"><p>'.get_post_meta(get_the_ID(),'wp_party_pitch', true).'</p></div></div></div></div>';
                        endwhile;
                    }
                    /**Vérifie si l'utilisateur est un maître de jeu */
                    foreach(get_user_meta(get_current_user_id(), 'hydRole') as $value){
                        if($value == 'GM'){
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
                            </div></div></div>';
                        }
                        
                    }
                    
                    $html .= '</div>';
                    
                    /**Fin de la boucle */
                }
            }
            /**Vérifie si l'utilisateur a déjà un rôle */
            if(get_user_meta(get_current_user_id(), 'hydRole') == false){
                
                $html .= '<br>
                <p>Si vous avez acheté un billet pour les hydriades, c\'est ici que vous devez entrez votre numéro de billet pour pouvoir proposer et participer à des parties</p>
                <form enctype="multipart/form-data" action="" name="becomeGM" id="becomeGM" method="post">
                Numéro de billet :<br>
                <input type="text" name="billetGM">
                <input name="userID" type="hidden" value="'.get_current_user_id().'">
                <input type="submit" value="Je veux devenir un maître de jeu !">
                </form><br>
                <form enctype="multipart/form-data" action="" name="becomePL" id="becomePL" method="post">
                Numéro de billet :<br>
                <input type="text" name="billetPL">
                <input name="userID" type="hidden" value="'.get_current_user_id().'">
                <input type="submit" value="Je veux devenir un joueur !">
                </form>';
                
                
            }
            
            wp_reset_postdata();
        }
        else{
            $html .="<h2>Vous devez vous connecter pour pouvoir voir les parties des hydriades</h2>";
        }
        return $html;
    }
    /**Permet d'ajouter une partie */
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
    }
    /**Permet l'ajout d'attente à l'utilisateurs voulant devenir MJ ou joueur */
    public function plOrGmAdd(){
        if(!empty($_POST['billetGM']) && !empty($_POST['userID'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userID'])))){
                update_user_meta($_POST['userID'], 'hydRole', 'waitGM');
                update_user_meta($_POST['userID'], 'hydBillet', esc_attr(strip_tags($_POST['billetGM'])));
            }
            else{
                
            }
        }
        if(!empty($_POST['billetPL']) && !empty($_POST['userID'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userID'])))){
                update_user_meta($_POST['userID'], 'hydRole', 'waitPL');
                update_user_meta($_POST['userID'], 'hydBillet', esc_attr(strip_tags($_POST['billetPL'])));
            }
            else{

            }
        }
    }
}
$wp_hydriade_shortcode = new wp_hydriade_shortcode;

?>