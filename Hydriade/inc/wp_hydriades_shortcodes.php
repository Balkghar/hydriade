<?php
class wp_hydriade_shortcode{
    /**Fonction de construction de la partie shortcode de l'extension */
    public function __construct(){
        add_action('init', array($this,'register_hydriade_shortcodes')); //shortcodes
        add_action('init', array($this,'form_info'));

        /**Ajax function */

        add_action('wp_ajax_showParties', array($this, 'showParties'));
        add_action('wp_ajax_nopriv_showParties', array($this, 'showParties'));

        add_action('wp_ajax_form_info', array($this, 'form_info'));
        add_action('wp_ajax_nopriv_form_info', array($this, 'form_info'));


        /**End Ajax function */


        add_action('showPartiesFunc', array($this, 'showParties'));
    }
    public function register_hydriade_shortcodes(){
        add_shortcode('wp_parties', array($this,'show_parties'));
    }
    /**
     * Fonction permettant de montrer les parties aux clients connectés
     * 
     */
    public function show_parties(){

        
        /**vérifie si l'utilisateurs est en ligne */
        if(is_user_logged_in())
        {
            wp_footer();
            echo '
            <form class="languageForm">
            Langue : <br>
            <table class="languageTable">
                <tr>
                    <td><label>Français : </label></td>
                    <td><input class="language" type="checkbox" id="french" name="french" value="French" checked></td>
                </tr>
                <tr>
                    <td><label>Anglais : </label></td>
                    <td><input class="language" type="checkbox" id="english" name="english" value="Anglais" checked></td>
                </tr>
                <tr>
                    <td><label>Allemand : </label></td>
                    <td><input class="language" type="checkbox" id="deutsch" name="deutsch" value="Allemand" checked></td>
                </tr>
            </table>
            </form>';

            echo '<div id="answer">';

            /**Déclaration du tableau des langues */
            

            do_action('showPartiesFunc');
        }
        else{
            echo "<h2>Vous devez vous connecter pour pouvoir voir les parties des hydriades</h2>";
        }
        echo '</div>';
    }
    /**Fonction permettant l'ajout d'un joueur à une partie */
    public function form_info(){

            $current_user = get_currentuserinfo();

            $email = $current_user->user_email;

            $urlSite = get_option('NameMail');
            $headers .= "Reply-To: Hydriade <".$urlSite.">\r\n";
            $headers .= "Return-Path: Hydriade <".$urlSite.">\r\n";
            $headers .= "From: Hydriade <".$urlSite.">\r\n"; 
            $headers .= "L'association de l'hydre\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/plain; charset=utf-8\r\n";
            $headers .= "X-Priority: 3\r\n";
            $headers .= "X-Mailer: PHP". phpversion() ."\r\n";


            if(!empty($_POST['userID']) && !empty($_POST['postID'])){
                if(get_userdata(esc_attr(strip_tags($_POST['userID']))) &&  get_post(esc_attr(strip_tags($_POST['postID'])))){
                    update_user_meta($_POST['userID'], 'Party'.$_POST['postID'], 'registeredWait');
                    wp_mail($email, 'Inscription à '.str_replace('&#8217;','\'',get_the_title($_POST['postID'])), 'Votre inscription à la partie "'.get_the_title($_POST['postID']).'" a bien été reçu.', $headers);
                }
            }
            if(!empty($_POST['userIDCan']) && !empty($_POST['postIDCan'])){
                if(get_userdata(esc_attr(strip_tags($_POST['userIDCan']))) &&  get_post(esc_attr(strip_tags($_POST['postIDCan'])))){
                    delete_user_meta($_POST['userIDCan'], 'Party'.$_POST['postIDCan'], 'registeredWait');
                    wp_mail($email, 'Annulation  d\'inscription à '.str_replace('&#8217;','\'',get_the_title($_POST['postIDCan'])), 'Votre annulation d\'inscription à la partie "'.get_the_title($_POST['postIDCan']).'" a bien été reçu.', $headers);
                }
            }    
            if(!empty($_POST['userIDDes']) && !empty($_POST['postIDDes'])){
                if(get_userdata(esc_attr(strip_tags($_POST['userIDDes']))) && get_post(esc_attr(strip_tags($_POST['postIDDes'])))){

                    delete_user_meta($_POST['userIDDes'], 'Party'.$_POST['postIDDes'], 'Registered');

                    $author_id = get_post_field ('post_author', $_POST['postIDDes']);

                    $GM_user = get_user_by('ID',$author_id);

                    $GM_mail = $GM_user->user_email;

                    wp_mail($email, 'Désincription à '.str_replace('&#8217;','\'',get_the_title($_POST['postIDDes'])), 'Votre Désincription à la partie "'.get_the_title($_POST['postIDDes']).'" a bien été reçu.', $headers);

                    wp_mail($GM_mail, $current_user->display_name." s'est désinscrit pour la partie ".str_replace('&#8217;','\'',get_the_title($_POST['postIDDes'])), "Un joueur s'est désinscrit à votre partie : ".$current_user->display_name."\nMail de contact : ". $email, $headers);

                }
            }
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
            if(!empty($_POST['userIDdel_party']) && !empty($_POST['postIDdel_party'])){
                $authr_id = get_post_field ('post_author',$_POST['postIDdel_party']);
                if($authr_id == get_current_user_id()){
                    wp_trash_post($_POST['postIDdel_party']);
                }
            }
            /**Vérifie si les données sont la et créé une metadata qui indique que le client est en attente de confirmation de son rôle */
            if(!empty($_POST['billetGM']) && !empty($_POST['userID'])  && !empty($_POST['regime'])){
                if(get_userdata(esc_attr(strip_tags($_POST['userID'])))){
                    update_user_meta($_POST['userID'], 'hydRole', 'waitGM');
                    update_user_meta($_POST['userID'], 'hydBillet', esc_attr(strip_tags($_POST['billetGM'])));
                    update_user_meta($_POST['userID'], 'regime', esc_attr(strip_tags($_POST['regime'])));
                    wp_mail($email, 'Inscription pour les hydriades', 'Votre inscription pour les hydriades en tant que MJ a bien été reçue.', $headers);
                }
            }
            if(!empty($_POST['billetPL']) && !empty($_POST['userID']) && !empty($_POST['regime'])){
                if(get_userdata(esc_attr(strip_tags($_POST['userID'])))){
                    update_user_meta($_POST['userID'], 'hydRole', 'waitPL');
                    update_user_meta($_POST['userID'], 'hydBillet', esc_attr(strip_tags($_POST['billetPL'])));
                    update_user_meta($_POST['userID'], 'regime', esc_attr(strip_tags($_POST['regime'])));
                    wp_mail($email, 'Inscription pour les hydriades', 'Votre inscription pour les hydriades en tant que joueu­·r·se a bien été reçue.', $headers);
                }
            }
    }
    public function showParties(){
        $ifLangue = array();
        if(!empty($_POST['language'])){
            if(is_array($_POST['language'])){
                $langue = $_POST['language'];
                foreach($langue as $v){
                    if($v == 'French'){
                        array_push($ifLangue, 'Français');
                    }
                    else{
                        array_push($ifLangue, $v);
                    }
                }
            }
            else{
                $ifLangue = array(
                    "Français",
                    "Anglais",
                    "Allemand"
                );
            }
            
        }
        else{
            $ifLangue = array(
                "Français",
                "Anglais",
                "Allemand"
            );
        }
        $Languages = array(
            "french" => "Français",
            "english" => "Anglais",
            "deutsch" => "Allemand"
        );
        
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
                    'post_status' => array('publish'),
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

                $registered = true;
                
                $write = true;
                
                foreach($postIDs as $postID){
                    if(get_user_meta(get_current_user_id(), 'Party'.$postID)){
                        $registered = false;
                    break;
                    }
                }
                foreach($postIDs as $postID){
                    $author_id = get_post_field ('post_author', $postID);
                    if($author_id == get_current_user_id()){
                        $write = false;
                    break;
                    }
                }


                /**Titre du du départ */
                echo '<h2 class="caTitle">'.$term->name.'</h2><div class="row">';
                if($loop->have_posts()) {
                    while($loop->have_posts()) : $loop->the_post();
                    /**Informations des parties */
                        $users = get_users(array('meta_key' => 'Party'.get_the_ID(), 'meta_value' => 'Registered'));

                        $numItems = count($users);
                        if(in_array(get_post_meta(get_the_ID(),'wp_party_language', true), $ifLangue)){
                            echo '<div class="column">
                        <div class="card">
                        <h3>'.get_the_title().'</h3>
                        <p><B>Univers de jeu : </B>'.get_post_meta(get_the_ID(),'wp_party_univers', true).'</p>
                        <p><B>Ambiance : </B>'.get_post_meta(get_the_ID(),'wp_party_ambiance', true).'</p>
                        <p><B>MJ : </B>'.get_post_meta(get_the_ID(),'wp_party_GM', true).'</p>
                        <p><B>Nombre de joueurs : </B>'.get_post_meta(get_the_ID(),'wp_party_players', true).'</p>
                        <p><B>Place restante : </B>'.(get_post_meta(get_the_ID(),'wp_party_players', true)-$numItems).'</p>
                        <p><B>Temps estimé : </B>'.get_post_meta(get_the_ID(),'wp_party_time', true).'h</p>
                        <p><B>Langue : </B>'.get_post_meta(get_the_ID(),'wp_party_language', true).'</p>';
                        echo '<p><b>Joueu·r·se·s inscrit·e·s : </b>';
                        
                        $i = 0;

                        foreach($users as $user){

                            if(++$i === $numItems) {
                                echo $user->display_name;
                            }
                            else{
                                echo $user->display_name.', ';
                            }
                        }
                        echo '</p>
                        <div class="pitch" id= "pitch'.get_the_ID().'">
                            <div class="block" onclick="showOrHide('.get_the_ID().')">
                                <button class="buPitch" id="buPitch'.get_the_ID().'">Pitch du scénario</button>
                            </div>
                            <div id="'.get_the_ID().'" class="displayNone"><p class="white-space">'.get_post_meta(get_the_ID(),'wp_party_pitch', true).'</p>';
                        echo 
                            '</div>
                        </div>';
                        /**Vérifie si l'utilisateurs a le rôle nécessaire de s'inscrire à une partie */
                        foreach(get_user_meta(get_current_user_id(), 'hydRole') as $value){
                            if($value == 'GM' || $value == 'PL'){
                                $answer = get_user_meta(get_current_user_id(),'Party'.get_the_ID());
                                if($answer){
                                    foreach($answer as $valu){
                                        if($valu == "Registered"){
                                            echo 
                                            '<br>
                                            <form enctype="multipart/form-data" action="" name="desin" id="desin" method="post">
                                            <input type="hidden" name="userIDDes" value="'.get_current_user_id().'">
                                            <input type="hidden" name="postIDDes" value="'.get_the_ID().'">
                                            <input type="submit" value="Se désinscrire de la partie">
                                            </form>
                                            ';
                                        }
                                        else if($valu == "registeredWait"){
                                            echo 
                                            '<br>
                                            <form enctype="multipart/form-data" action="" name="cancel" id="cancel" method="post">
                                            <input type="hidden" name="userIDCan" value="'.get_current_user_id().'">
                                            <input type="hidden" name="postIDCan" value="'.get_the_ID().'">
                                            <input type="submit" value="Annuler l\'inscription">
                                            </form>
                                            ';
                                        }
                                    }
                                }
                                else{
                                    if((get_post_meta(get_the_ID(),'wp_party_players', true)-$numItems) >= 1){
                                        
                                        if($registered && $write){
                                            echo 
                                            '<br>
                                            <form enctype="multipart/form-data" action="" name="add_player" id="add_player" method="post">
                                            <input type="hidden" name="userID" value="'.get_current_user_id().'">
                                            <input type="hidden" name="postID" value="'.get_the_ID().'">
                                            <input onclick="disableSubmit" type="submit" value="S\'inscrire à la partie">
                                            </form>
                                            ';
                                        }
                                    }
                                }
                                $authr_id = get_post_field ('post_author',get_the_ID());
                                if($authr_id == get_current_user_id()){
                                    echo 
                                    '<br>
                                    <form enctype="multipart/form-data" action="" name="del_party" id="del_party" method="post">
                                    <input type="hidden" name="userIDdel_party" value="'.get_current_user_id().'">
                                    <input type="hidden" name="postIDdel_party" value="'.get_the_ID().'">
                                    <input type="submit" value="Supprimer la partie">
                                    </form>
                                    ';
                                }
                                
                            }
                        }
                        echo '</div></div>';
                    
                    }
                endwhile;
                }
                $current_user = wp_get_current_user();
                /**Vérifie si l'utilisateur est un maître de jeu */
                foreach(get_user_meta(get_current_user_id(), 'hydRole') as $value){
                    if($value == 'GM'){
                        if($write && $registered){
                            echo '<div class="column"><div class="card"><button onclick="showOrHide('.$term->term_id.')"><h3><b>+Ajouter une partie+</b></h3></button><div id="'.$term->term_id.'" class="displayNone">
                            <form enctype="multipart/form-data" action="" name="new_post" id="new_post" method="post">
                            Titre de la partie :<br>
                            <input type="text" name="title">
                            Univers du jeu :<br>
                            <input type="text" name="universe">
                            Ambiance :<br>
                            <input type="text" name="ambiance">
                            Maître de jeu :<br>
                            <input type="text" name="MJ" value="'.$current_user->display_name.'">
                            Nombre de joueurs :<br>
                            <select name="players">';
                            for($i = 1; $i <= 10; $i++){
                                    echo'<option value="'.$i.'">'.$i.'</option>';
                            }
                            echo '</select>
                            Temps estimé :<br>
                            <select name="time">';
                            for($i = 1; $i <= 30; $i++){
                                    echo'<option value="'.$i.'">'.$i.'</option>';
                            }
                            echo '</select>
                            Langue :<br>
                            <select id="wp_party_language" name="language">';
                            foreach ($Languages as $Langue){
                                echo '<option value="'.$Langue.'">'.$Langue.'</option>';
                            }
                            echo  '</select>';
                            echo 'Pitch du scénario :<br>
                            <textarea class="white-space" name="pitch"></textarea>
                            <input name="category" type="hidden" value="'.$term->term_id.'">
                            <input type="submit" value="Envoyer">
                            </form>
                            </div></div></div>';                   
                        }

                        /**Affichage du formulaire pour créer une partie */
                        
                    }
                    
                }
                
                echo '</div>';
                
                /**Fin de la boucle */
            }
        }
        /**Vérifie si l'utilisateur a déjà un rôle */
        if(get_user_meta(get_current_user_id(), 'hydRole') == false){
            /**Affichage pour s'inscrire en tant que joueurs ou MJ */
            echo '<br>
            <p>Si vous avez acheté un billet pour les hydriades, c\'est ici que vous devez entrez votre numéro de billet pour pouvoir proposer et participer à des parties</p>
            <form enctype="multipart/form-data" action="" name="becomeGM" id="becomeGM" method="post">
            Régime alimentaire : 
            <input type="radio" name="regime" value="Vege"> Végétarien
            <input type="radio" name="regime" value="Normal" checked> Normal<br>
            Numéro de billet :<br>
            <input type="text" name="billetGM">
            <input name="userID" type="hidden" value="'.get_current_user_id().'">
            <input type="submit" value="Je veux devenir un maître de jeu !">
            </form><br>
            <form enctype="multipart/form-data" action="" name="becomePL" id="becomePL" method="post">
            Régime alimentaire : 
            <input type="radio" name="regime" value="Vege"> Végétarien
            <input type="radio" name="regime" value="Normal" checked> Normal<br>
            Numéro de billet :<br>
            <input type="text" name="billetPL">
            <input name="userID" type="hidden" value="'.get_current_user_id().'">
            <input type="submit" value="Je veux devenir un joueur !">
            </form>';
        }
        
        wp_reset_postdata();
        die();

    }
}
$wp_hydriade_shortcode = new wp_hydriade_shortcode;

?>