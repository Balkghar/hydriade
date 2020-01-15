<?php
class wp_hydriade_shortcode{
    /**Fonction de construction de la partie shortcode de l'extension */
    public function __construct(){
        add_action('init', array($this,'register_hydriade_shortcodes')); //shortcodes
        add_action('init', array($this,'partie_add'));
        add_action('init', array($this,'player_add_partie'));
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
        /**Déclaration du tableau des langues */
        $Languages = array(
            "french" => "Français",
            "english" => "Anglais",
            "deutsch" => "Allemand"
        );
        /**vérifie si l'utilisateurs est en ligne */
        if(is_user_logged_in())
        {
            /**Permet d'obtenir la taxonomy qu'on a créé avant */
            $terms = get_terms(array(
                'taxonomy' => 'types',
                'hide_empty' => false,
                'orderby' => 'ID',
                'order'   => 'ASC',
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
                                'orderby' => 'date',
                                'order'   => 'ASC',
                            ),
                        ),
                    );

                    /**Affichage des informations pour les parties */
                    /**Loop des posts séléctionnés */
                    $postIDs = array();
                    $loop = new WP_Query($args);
                    if($loop->have_posts()) {
                        while($loop->have_posts()) : $loop->the_post();
                        /**Informations des parties */
                            array_push($postIDs,get_the_ID());
                        endwhile;
                    }

                    /**Titre du du départ */
                    $html .= '<h2 class="caTitle">'.$term->name.'</h2><div class="row">';
                    if($loop->have_posts()) {
                        while($loop->have_posts()) : $loop->the_post();
                        /**Informations des parties */
                            $users = get_users(array('meta_key' => 'Party'.get_the_ID(), 'meta_value' => 'Registered'));

                            $numItems = count($users);

                            $i = 0;

                            $html .= '<div class="column">
                            <div class="card">
                            <h3>'.get_the_title().'</h3>
                            <p><B>Univers de jeu : </B>'.get_post_meta(get_the_ID(),'wp_party_univers', true).'</p>
                            <p><B>Ambiance : </B>'.get_post_meta(get_the_ID(),'wp_party_ambiance', true).'</p>
                            <p><B>MJ : </B>'.get_post_meta(get_the_ID(),'wp_party_GM', true).'</p>
                            <p><B>Nombre de joueurs : </B>'.get_post_meta(get_the_ID(),'wp_party_players', true).'</p>
                            <p><B>Place restante : </B>'.(get_post_meta(get_the_ID(),'wp_party_players', true)-$numItems).'</p>
                            <p><B>Temps estimé : </B>'.get_post_meta(get_the_ID(),'wp_party_time', true).'h</p>
                            <p><B>Langue : </B>'.get_post_meta(get_the_ID(),'wp_party_language', true).'</p>';
                            $html .= '<p><b>Joueu·r·se·s inscrit·e·s : </b>';
                            
                            foreach($users as $user){

                                if(++$i === $numItems) {
                                    $html .= $user->display_name;
                                }
                                else{
                                    $html .= $user->display_name.', ';
                                }
                            }
                            $html .= '</p>
                            <div class="pitch"><button onclick="showOrHide('.get_the_ID().')">Pitch du scénario<div class="more">+</div></button>
                            <div id="'.get_the_ID().'" class="displayNone"><p>'.get_post_meta(get_the_ID(),'wp_party_pitch', true).'</p>';
                            $html .= '</div></div>';
                            /**Vérifie si l'utilisateurs a le rôle nécessaire de s'inscrire à une partie */
                            foreach(get_user_meta(get_current_user_id(), 'hydRole') as $value){
                                if($value == 'GM' || $value == 'PL'){
                                    $answer = get_user_meta(get_current_user_id(),'Party'.get_the_ID());
                                    if($answer){
                                        foreach($answer as $valu){
                                            if($valu == "Registered"){
                                                $html .= 
                                                '<br>
                                                <form enctype="multipart/form-data" action="" name="desin" id="desin" method="post">
                                                <input type="hidden" name="userIDDes" value="'.get_current_user_id().'">
                                                <input type="hidden" name="postIDDes" value="'.get_the_ID().'">
                                                <input type="submit" value="Se désinscrire de la partie">
                                                </form>
                                                ';
                                            }
                                        }
                                    }
                                    else{
                                        if((get_post_meta(get_the_ID(),'wp_party_players', true)-$numItems) >= 1){
                                            
                                            $registered = true;
                                            
                                            foreach($postIDs as $postID){
                                                if(get_user_meta(get_current_user_id(), 'Party'.$postID)){
                                                    $registered = false;
                                                break;
                                                }
                                            }
                                            if($registered){
                                                $html .= 
                                                '<br>
                                                <form enctype="multipart/form-data" action="" name="add_player" id="add_player" method="post">
                                                <input type="hidden" name="userID" value="'.get_current_user_id().'">
                                                <input type="hidden" name="postID" value="'.get_the_ID().'">
                                                <input type="submit" value="S\'inscrire à la partie">
                                                </form>
                                                ';
                                            }
                                            
                                        }
                                        else{

                                        }

                                    }
                                    
                                }
                            }
                            $html .= '</div></div>';
                        endwhile;
                        
                        
                    }
                    /**Vérifie si l'utilisateur est un maître de jeu */
                    foreach(get_user_meta(get_current_user_id(), 'hydRole') as $value){
                        if($value == 'GM'){
                            /**Affichage du formulaire pour créer une partie */
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
                /**Affichage pour s'inscrire en tant que joueurs ou MJ */
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
    /**Fonction permettant l'ajout d'un joueur à une partie */
    public function player_add_partie(){
        $current_user = get_currentuserinfo();

        $email = $current_user->user_email;

        $admin_email = get_option('admin_email');

        if(!empty($_POST['userID']) && !empty($_POST['postID'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userID']))) &&  get_post(esc_attr(strip_tags($_POST['postID'])))){
                update_user_meta($_POST['userID'], 'Party'.$_POST['postID'], 'registeredWait');
                wp_mail($email, 'Inscription', 'Votre inscription a été reçu');
            }
        }
    }
    /**Permet d'ajouter une partie */
    public function partie_add(){
        /**Vérifie si les données sont là */
        if(!empty($_POST['title']) && !empty($_POST['universe']) && !empty($_POST['ambiance']) && !empty($_POST['MJ']) && !empty($_POST['players']) && !empty($_POST['language']) && !empty($_POST['pitch']) && !empty($_POST['category'])  && !empty($_POST['time'])){
            /**Créé le post et sauvegarde son ID */
            $post_id = wp_insert_post(array(
                'post_title' => esc_attr(strip_tags($_POST['title'])),
				'post_type' => 'wp_parties',
                'post_status' => 'pending'
            ));
            /** Associe le post à la category que le MJ a choisi*/
            $term = get_term(esc_attr(strip_tags($_POST['category'])) , 'types');
            wp_set_object_terms($post_id, $term->name, 'types');

            /**Ajoute les données au post */
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
        /**Vérifie si les données sont la et créé une metadata qui indique que le client est en attente de confirmation de son rôle */
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
        if(!empty($_POST['userIDDes']) && !empty($_POST['postIDDes'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDDes']))) && get_post(esc_attr(strip_tags($_POST['postIDDes'])))){
                delete_user_meta($_POST['userIDDes'], 'Party'.$_POST['postIDDes'], 'Registered');
                
            }
        }
    }
}
$wp_hydriade_shortcode = new wp_hydriade_shortcode;

?>