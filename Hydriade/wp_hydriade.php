<?php
/**
 * Plugin Name:       Hydriade
 * Plugin URI:        -
 * Description:       Plugin pour gérer les inscriptions aux parties de JDR des hydriades
 * Version:           2.0.0
 * Requires at least: 5.8.3
 * Requires PHP:      7.2
 * Author:            Hugo Germano 
 * Author URI:        https://germa.no
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */
class wp_hydriade{
    //fonction qui s'éxecute lorsque la classe est créé
    public function __construct(){
        add_action('admin_menu', array($this,'custom_menu'));
        add_action('init', array($this, 'register_parties'));
        add_action('init', array($this,'addGMOrPLW'));
        add_action('init', array($this,'gestInscr'));
        add_action('init', array($this,'exportPDF'));
        add_action('publish_to_trash', array($this,'prevent_user'));
        add_action('pending_to_publish', array($this,'prevent_gm'));
        add_action( 'admin_init', array($this,'hydriade_register_settings') );
        add_action('admin_menu', array($this,'hydriade_register_options_page'));
        add_action('add_meta_boxes', array($this,'add_party_meta_boxes')); //add meta boxes
        add_filter('the_content', array($this,'prepend_party_meta_to_content')); //gets our meta data and dispayed it before the content
        add_action('save_post_wp_parties', array($this,'save_party')); //save party
        add_action('wp_enqueue_scripts', array($this,'enqueue_public_scripts_and_styles')); //public scripts and styles
        add_action('admin_enqueue_scripts', array($this,'enqueue_admin_scripts_and_styles'));
        register_activation_hook(__FILE__, array($this,'plugin_activate')); //activate hook
        register_deactivation_hook(__FILE__, array($this,'plugin_deactivate')); //deactivate hook
        add_action( 'init', array($this,'add_custom_taxonomy'), 0 );
    }
    //Créé un menu sur l'administration de wordpress
    function custom_menu() { 

        add_menu_page( 
            'Hydriade', 
            'Hydriade', 
            'manage_options', 
            'hydriade', 
            array($this,'admin_hydriade'), 
            'dashicons-portfolio',
            6
        );
        /**Différents submenu pour le menu des hydriades */
        add_submenu_page('hydriade', 'Gestion des rôles', 'Gestion des rôles', 'edit_posts', 'admin_HydRole',array($this,'gestRole'));
        add_submenu_page('hydriade', 'Gestion des inscriptions', 'Gestion des inscriptions', 'edit_posts', 'admin_HydPartie',array($this,'gestPlayer'));
        add_submenu_page('hydriade', 'Exportation des parties', 'Exportation des parties', 'edit_posts', 'export_pdf',array($this,'Export_pdf'));
        add_submenu_page('hydriade', 'Ajouter un départ', 'Ajouter un départ', 'edit_posts','edit-tags.php?taxonomy=types');
    }
    /**Page d'accueil de l'extension */
    function admin_hydriade(){
        echo "<h1>Hydriade</h1>";
    }
    function prevent_gm($post){
        $urlSite = get_option('NameMail');
        $headers = "Return-Path: Hydriade <".$urlSite.">\r\n";
        $headers .= "Reply-To: Hydriade <".$urlSite.">\r\n";
        $headers .= "L'association de l'hydre\r\n";
        $headers .= "From: Hydriade <".$urlSite.">\r\n"; 
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=utf-8\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "X-Mailer: PHP". phpversion() ."\r\n";

        if('wp_parties' == $post->post_type) {

            $author_id = get_post_field ('post_author', $post->ID);

            $GM_user = get_user_by('ID',$author_id);

            $GM_mail = $GM_user->user_email;
            wp_mail($GM_mail, 'La partie "'.str_replace('&#8217;','\'',get_the_title($post->ID)).'" a été publiée', str_replace('&#8217;','\'',"Cher MJ, merci d’avoir proposé ta partie aux Hydriades! Elle a été publiée !\nTu trouveras ci-dessous les informations relatives à ta partie et tu recevras un mail lorsque des joueurs s’inscriront à celle-ci.\nMerci encore et à tout bientôt!\n\nTitre : ".get_the_title($post->ID)."\nUnivers de jeu : ".get_post_meta($post->ID,"wp_party_univers", true)."\nAmbiance : ".get_post_meta($post->ID,"wp_party_ambiance", true)."\nMJ : ".get_post_meta($post->ID,"wp_party_GM", true)."\nNombre de joueurs : ".get_post_meta($post->ID,"wp_party_players", true)."\nTemps estimé : ".get_post_meta($post->ID,"wp_party_time", true)."h\nLangue : ".get_post_meta($post->ID,"wp_party_language", true).""), $headers);
        }

    }
    function prevent_user($post){

        $urlSite = get_option('NameMail');
        $headers = "Return-Path: Hydriade <".$urlSite.">\r\n";
        $headers .= "Reply-To: Hydriade <".$urlSite.">\r\n";
        $headers .= "L'association de l'hydre\r\n";
        $headers .= "From: Hydriade <".$urlSite.">\r\n"; 
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=utf-8\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "X-Mailer: PHP". phpversion() ."\r\n";


        if('wp_parties' == $post->post_type) {
            $users = get_users(array('meta_key' => 'Party'.$post->ID, 'meta_value' => 'Registered'));
            foreach($users as $user){
                delete_user_meta($user->ID, 'Party'.$post->ID, 'registeredWait');
                $user_mail = $user->user_email;
                wp_mail($user_mail, 'La partie "'.str_replace('&#8217;','\'',get_the_title($post->ID)).'" a été supprimée', 'La partie "'.str_replace('&#8217;','\'',get_the_title($post->ID)).'" a été supprimée', $headers);
                
            }

            
            $author_id = get_post_field ('post_author', $post->ID);

            $GM_user = get_user_by('ID',$author_id);

            $GM_mail = $GM_user->user_email;

            wp_mail($GM_mail, 'La partie "'.str_replace('&#8217;','\'',get_the_title($post->ID)).'" a été supprimée', 'La partie "'.str_replace('&#8217;','\'',get_the_title($post->ID)).'" a été supprimée', $headers);

        }
        
    }
    function Export_pdf(){
        echo '<h1>Exportation des parties en pdf</h1>
        <p>Cliquez sur le bouton pour exporter en pdf</p>
        <form action="" method="post">
        <input type="hidden" name="export" value="export">
        <input type="submit" value="Exporter les parties en PDF">
        </form>
        ';
    }
    /**
     * Fonction permettant l'exportation des informations des parties
     */
    function exportPDF(){
        if(!empty($_POST['export'])){
            $pdf=new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetMargins(20, 20, 20, true);
            $pdf->AddPage('P',"A4");
            $pdf->writeHTML('<h1>Hydriade</h1>');

            
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
                    
                    $pdf->AddPage('L',"A4");
                    $pdf->writeHTML('<h2>'.$term->name.'</h2>');
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
                    $loop = new WP_Query($args);
                    if($loop->have_posts()) {
                        while($loop->have_posts()) : $loop->the_post();
                        $pdf->writeHTML('<h3>'.get_the_title().' / N° de table : '.get_post_meta(get_the_ID(),'wp_party_table', true).'</h3>');
                        $content = '<table><tr style="font-weight: bold;"><th>Nom ou pseudo</th><th>Régime alimentaire</th></tr>';
                        $users = get_users(array('meta_key' => 'Party'.get_the_ID(), 'meta_value' => 'Registered'));
                        $authr_id = get_post_field ('post_author',get_the_ID());
                        $author = get_user_by('ID', $authr_id);
                        foreach($users as $user){
                            foreach(get_user_meta($user->ID, 'regime') as $regime){
                                $content .= '<tr><td>'.$user->display_name.'</td><td>'.$regime.'</td></tr>';
                            }
                        }
                        foreach(get_user_meta($authr_id, 'regime') as $regime){
                            $content .= '<tr><td>'.$author->display_name.'</td><td>'.$regime.'</td></tr>';
                        }
                        $content .= '</table>';

                        $pdf->writeHTML($content);

                        endwhile;
                    }
                }
            }
            
            $pdf->Output('parties.pdf', 'D');
        }
    }
    /**Page d'inscription des parties de la part des joueurs */
    function gestPlayer(){
        echo "<h1>Gestion des inscriptions aux parties</h1>";


        $terms = get_terms(array(
            'taxonomy' => 'types',
            'hide_empty' => true,
            'orderby' => 'ID',
            'order'   => 'ASC',
        ));
        /**Vérifie si le tableau n'est pas vide */
        if(!empty($terms)){
                /**Boucle pour afficher les éléments du tableau */
                echo '<h2>Inscription en attente</h2>';
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

                    $loop = new WP_Query($args);
                    /**Titre du du départ */
                    echo '<h3>'.$term->name.'</h3>';
                    if($loop->have_posts()) {
                        while($loop->have_posts()) : $loop->the_post();
                        $users = get_users(array('meta_key' => 'Party'.get_the_ID(), 'meta_value' => 'registeredWait'));
                        if(!empty($users))
                        echo '<h4>'.get_the_title().'</h4>';
                        foreach($users as $user){
                            echo '<table><tr><th>Nom ou pseudo</th><th>Accepter</th><th>Refuser</th></tr>';
                            echo '<tr><td>'. $user->display_name.'</td><td><form enctype="multipart/form-data" action="" name="Accept" id="Accept" method="post"><input type="hidden" name="userIDInscr" value="'.$user->ID.'"><input type="hidden" name="postIDInscr" value="'.get_the_ID().'"><input type="submit" value="Accepter l\'inscription"></form></td><td><form enctype="multipart/form-data" action="" name="Refus" id="Refus" method="post"><input type="hidden" name="userIDRefu" value="'.$user->ID.'"><input type="hidden" name="postIDRefu" value="'.get_the_ID().'"><input type="submit" value="Refuser l\'inscription"></form></td></tr>';
                            echo '</table>';
                        }
                        endwhile;
                        
                    }
                    
                    /**Fin de la boucle */
                }
                echo '<h2>Inscription acceptée</h2>';

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

                    $loop = new WP_Query($args);
                    /**Titre du du départ */
                    echo '<h3>'.$term->name.'</h3>';
                    if($loop->have_posts()) {
                        while($loop->have_posts()) : $loop->the_post();
                        $users = get_users(array('meta_key' => 'Party'.get_the_ID(), 'meta_value' => 'Registered'));
                        if(!empty($users))
                        echo '<B><h4>'.get_the_title().'</h4></B>';
                        foreach($users as $user){
                            echo '<table><tr><th>Nom ou pseudo</th><th>Désinscrire</th></tr>';
                            echo '<tr><td>'. $user->display_name.'</td><td><form enctype="multipart/form-data" action="" name="De" id="De" method="post"><input type="hidden" name="userIDDe" value="'.$user->ID.'"><input type="hidden" name="postIDDe" value="'.get_the_ID().'"><input type="submit" value="Désinscrire"></form></td></tr>';
                            echo '</table>';
                        }
                        endwhile;
                        
                    }
                    
                    /**Fin de la boucle */
                }


        }
    }
    /**Fonction pour gérer les inscriptons aux parties */
    function gestInscr(){
        $urlSite = get_option('NameMail');
        $headers = "Return-Path: Hydriade <".$urlSite.">\r\n";
        $headers .= "Reply-To: Hydriade <".$urlSite.">\r\n";
        $headers .= "L'association de l'hydre\r\n";
        $headers .= "From: Hydriade <".$urlSite.">\r\n"; 
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=utf-8\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "X-Mailer: PHP". phpversion() ."\r\n";

        if(!empty($_POST['userIDInscr']) && !empty($_POST['postIDInscr'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDInscr']))) && get_post(esc_attr(strip_tags($_POST['postIDInscr'])))){

                $current_user = get_user_by('ID',$_POST['userIDInscr']);

                $email = $current_user->user_email;

                $author_id = get_post_field ('post_author', $_POST['postIDInscr']);

                $GM_user = get_user_by('ID',$author_id);

                $GM_mail = $GM_user->user_email;

                update_user_meta($_POST['userIDInscr'], 'Party'.$_POST['postIDInscr'], 'Registered');
                
                wp_mail($email, "Inscription pour la partie ".str_replace('&#8217;','\'',get_the_title($_POST['postIDInscr'])), "Votre inscription pour la partie ".str_replace('&#8217;','\'',get_the_title($_POST['postIDInscr'])). " a été acceptée.", $headers);
                
                wp_mail($GM_mail, $current_user->display_name." s'est inscrit pour la partie ".str_replace('&#8217;','\'',get_the_title($_POST['postIDInscr']))."", "Un joueur s'est inscrit à votre partie : ".$current_user->display_name."\nMail de contact : ". $email, $headers);
            }
        }
        if(!empty($_POST['userIDRefu']) && !empty($_POST['postIDRefu'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDRefu']))) && get_post(esc_attr(strip_tags($_POST['postIDRefu'])))){

                $current_user = get_user_by('ID',$_POST['userIDRefu']);
                $email = $current_user->user_email;

                delete_user_meta($_POST['userIDRefu'], 'Party'.$_POST['postIDRefu'], 'registeredWait');

                wp_mail($email, "Inscription pour la partie ".str_replace('&#8217;','\'',get_the_title($_POST['postIDRefu'])), "Votre inscription pour la partie ".str_replace('&#8217;','\'',get_the_title($_POST['postIDRefu']))." a été refusée.", $headers);

            }
        }
        if(!empty($_POST['userIDDe']) && !empty($_POST['postIDDe'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDDe']))) && get_post(esc_attr(strip_tags($_POST['postIDDe'])))){
                
                $current_user = get_user_by('ID',$_POST['userIDDe']);
                $email = $current_user->user_email;
                
                $author_id = get_post_field ('post_author', $_POST['postIDInscr']);

                $GM_user = get_user_by('ID',$author_id);

                $GM_mail = $GM_user->user_email;

                delete_user_meta($_POST['userIDDe'], 'Party'.$_POST['postIDDe'], 'Registered');

                wp_mail($email, 'Désinscription pour la partie '.str_replace('&#8217;','\'',get_the_title($_POST['postIDDe'])), 'Vous avez été désincrit de la partie '.str_replace('&#8217;','\'',get_the_title($_POST['postIDDe'])), $headers);
                
                wp_mail($GM_mail, $current_user->display_name." a été désinscrit pour la partie ".str_replace('&#8217;','\'',get_the_title($_POST['postIDInscr'])), "Un joueur a été désinscrit à votre partie : ".$current_user->display_name."\nMail de contact : ". $email, $headers);

            }
        }
    }
    /**Page de gestion des différents rôles pour les hydriades */
    function gestRole(){
        echo "<h1>Gestion des rôles pour les hydriades</h1>";
        $users = get_users(array('meta_key' => 'hydRole', 'meta_value' => 'waitGM'));
        /**Affichage des personnes demandant une confirmation ou qui peuvent être supprimé*/
        echo '<h2>Personne voulant devenir MJ</h2><table><tr><th>Nom ou pseudo</th><th>Numéro de billet</th></tr>';
        foreach($users as $user){
            foreach(get_user_meta($user->ID, 'hydBillet') as $value){
                echo '<tr><td>'. $user->display_name.'</td><td>'.$value.'</td><td><form enctype="multipart/form-data" action="" name="becomeGMW" id="becomeGMW" method="post"><input type="hidden" name="userIDGM" value="'.$user->ID.'"><input type="submit" value="Promouvoir maître de jeu"></form></td></tr>';
            }
        }
        echo '</table>';
        $users2 = get_users(array('meta_key' => 'hydRole', 'meta_value' => 'waitPL'));
        echo '<h2>Personne voulant devenir joueur</h2><table><tr><th>Nom ou pseudo</th><th>Numéro de billet</th></tr>';
        foreach($users2 as $user){
            foreach(get_user_meta($user->ID, 'hydBillet') as $value){
                echo '<tr><td>'. $user->display_name.'</td><td>'.$value.'</td><td><form enctype="multipart/form-data" action="" name="becomePLW" id="becomePLW" method="post"><input type="hidden" name="userIDPL" value="'.$user->ID.'"><input type="submit" value="Promouvoir joueur"></form></td></tr>';
            }
        }
        echo '</table>';

        $users3 = get_users(array('meta_key' => 'hydRole', 'meta_value' => 'PL'));
        echo '<h2>Personne étant joueur</h2><table><tr><th>Nom ou pseudo</th><th>Numéro de billet</th></tr>';
        foreach($users3 as $user){
            foreach(get_user_meta($user->ID, 'hydBillet') as $value){
                echo '<tr><td>'. $user->display_name.'</td><td>'.$value.'</td><td><form enctype="multipart/form-data" action="" name="deletePLW" id="deletePLW" method="post"><input type="hidden" name="userIDPLD" value="'.$user->ID.'"><input type="submit" value="Supprimer joueur"></form></td></tr>';
            }
        }
        echo '</table>';

        $users4 = get_users(array('meta_key' => 'hydRole', 'meta_value' => 'GM'));
        echo '<h2>Personne étant maître de jeu</h2><table><tr><th>Nom ou pseudo</th><th>Numéro de billet</th></tr>';
        foreach($users4 as $user){
            foreach(get_user_meta($user->ID, 'hydBillet') as $value){
                echo '<tr><td>'. $user->display_name.'</td><td>'.$value.'</td><td><form enctype="multipart/form-data" action="" name="deleteGMW" id="deleteGMW" method="post"><input type="hidden" name="userIDGMD" value="'.$user->ID.'"><input type="submit" value="Supprimer maître de jeu"></form></td></tr>';
            }
        }
        echo '</table>';
    }
    /**Permet d'ajouter les utilisateurs voulant devenir MJ ou joueur et ayant été validé par un admin ou de les supprimer*/
    public function addGMOrPLW(){
        $urlSite = get_option('NameMail');
        $headers = "Return-Path: Hydriade <".$urlSite.">\r\n";
        $headers .= "Reply-To: Hydriade <".$urlSite.">\r\n";
        $headers .= "L'association de l'hydre\r\n";
        $headers .= "From: Hydriade <".$urlSite.">\r\n"; 
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=utf-8\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "X-Mailer: PHP". phpversion() ."\r\n";

        if(!empty($_POST['userIDGM'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDGM'])))){

                $current_user = get_user_by('ID', $_POST['userIDGM']);

                $email = $current_user->user_email;

                update_user_meta($_POST['userIDGM'], 'hydRole', 'GM');
                wp_mail($email, 'Confirmation d\'inscription pour les hydriades', 'Votre inscription pour les hydriades en tant que MJ a été acceptée.', $headers);
            }
        }
        if(!empty($_POST['userIDPL'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDPL'])))){
                
                $current_user = get_user_by('ID',$_POST['userIDPL']);

                $email = $current_user->user_email;

                update_user_meta($_POST['userIDPL'], 'hydRole', 'PL');

                wp_mail($email, 'Confirmation d\'inscription pour les hydriades', 'Votre inscription pour les hydriades en tant que joueu­·r·se a été acceptée.', $headers);

            }
        }
        if(!empty($_POST['userIDGMD'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDGMD'])))){
                delete_user_meta($_POST['userIDGMD'], 'hydRole', 'GM');
            }
        }
        if(!empty($_POST['userIDPLD'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDPLD'])))){
                delete_user_meta($_POST['userIDPLD'], 'hydRole', 'PL');
            }
        }
    }
    function register_parties(){
        //Labels for post type
        $labels = array(
            'name'               => 'Partie',
            'singular_name'      => 'Partie',
            'menu_name'          => 'Parties',
            'name_admin_bar'     => 'Parties',
            'add_new'            => 'Ajouter une nouvelle', 
            'add_new_item'       => 'Ajouter une nouvelle partie',
            'new_item'           => 'Nouvelle partie', 
            'edit_item'          => 'Modifier partie',
            'view_item'          => 'Voir partie',
            'all_items'          => 'Toutes les Parties',
            'search_items'       => 'Chercher les Parties',
            'parent_item_colon'  => 'Parent Party:', 
            'not_found'          => 'Pas de partie trouvé.', 
            'not_found_in_trash' => 'Pas de partie trouvé dans la poubelle.',
        );
        //arguments for post type
        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'publicly_queryable'=> true,
            'query_var'         => true,
            'hierarchical'      => false,
            'supports'          => array('title', 'author'),
            'has_archive'       => false,
            'show_in_menu'      => 'hydriade',
            'menu_position'     => null,
            'menu_icon'         => 'dashicons-book-alt',
            'rewrite'           => array('slug' => 'parties', 'with_front' => 'true')
        );
        //register post type
        register_post_type('wp_parties', $args);
    }
    //adding meta boxes for the party content type*/
    public function add_party_meta_boxes(){

        add_meta_box(
            'wp_party_meta_box', //id
            'Party Information', //name
            array($this,'party_meta_box_display'), //display function
            'wp_parties', //post type
            'normal', //party
            'default' //priority
        );
    }
    public function party_meta_box_display($post){
        //set nonce field
    wp_nonce_field('wp_party_nonce', 'wp_party_nonce_field');
    

    //collect variables
    $wp_party_GM = get_post_meta($post->ID,'wp_party_GM',true);
    $wp_party_ambiance = get_post_meta($post->ID,'wp_party_ambiance',true);
    $wp_party_univers = get_post_meta($post->ID,'wp_party_univers',true);
    $wp_party_pitch = get_post_meta($post->ID,'wp_party_pitch',true);
    $wp_party_language = get_post_meta($post->ID,'wp_party_language',true);
    $wp_party_time = get_post_meta($post->ID,'wp_party_time',true);
    $wp_party_players = get_post_meta($post->ID,'wp_party_players',true);
    $wp_party_table = get_post_meta($post->ID,'wp_party_table',true);

    ?>
    <p>Entrer les informations de votre partie</p>
    <div class="field-container">
        <?php 
        //before main form elementst hook
        do_action('wp_party_admin_form_start'); 
        ?>
        <div class="field">
            <label for="wp_party_GM">Le maître de jeu</label><br>
            <input type="text" name="wp_party_GM" id="wp_party_GM" value="<?php echo $wp_party_GM;?>"/>
        </div>
        <div class="field">
            <label for="wp_party_ambiance">L'ambiance</label><br>
            <input type="text" name="wp_party_ambiance" id="wp_party_ambiance" value="<?php echo $wp_party_ambiance;?>"/>
        </div>
        <div class="field">
            <label for="wp_party_univers">L'univers</label><br>
            <input type="text" name="wp_party_univers" id="wp_party_univers" value="<?php echo $wp_party_univers;?>"/>
        </div>
        <div class="field">
            <label for="wp_party_pitch">Quelques mots</label><br>
            <textarea class="white-space" name="wp_party_pitch" id="wp_party_pitch"><?php echo $wp_party_pitch;?></textarea>
        </div>
        <?php
        $Languages = array(
            "french" => "Français",
            "english" => "English",
            "deutsch" => "Deutsch",
        );
        echo '<div class="field">
        <label for="wp_party_language">La langue de votre partie</label>
        <select id="wp_party_language" name="wp_party_language">';
        foreach ($Languages as $Langue){
            if($wp_party_language == $Langue){
                echo'<option value="'.$Langue.'" selected>'.$Langue.'</option>';
            }
            else{
                echo'<option value="'.$Langue.'">'.$Langue.'</option>';
            }
        }
        echo '</select></div>';
        ?>
        <?php
        echo '<div class="field">
        <label for="wp_party_time">Le temps de votre partie</label>
        <select id="wp_party_time" name="wp_party_time">';
        for($i = 1; $i <= 30; $i++){
            if($wp_party_time == $i){
                echo'<option value="'.$i.'" selected>'.$i.'</option>';
            }
            else{
                echo'<option value="'.$i.'">'.$i.'</option>';
            }
        }
        echo '</select></div>';
        echo '<div class="field">
        <label for="wp_party_players">Le nombre de joueurs</label>
        <select id="wp_party_players" name="wp_party_players">';
        for($i = 1; $i <= 10; $i++){
            if($wp_party_players == $i){
                echo'<option value="'.$i.'" selected>'.$i.'</option>';
            }
            else{
                echo'<option value="'.$i.'">'.$i.'</option>';
            }
        }
        echo '</select></div>';
        ?>
        <div class="field">
            <label for="wp_party_table">Numéro de table</label><br>
            <input type="text" name="wp_party_table" id="wp_party_table" value="<?php echo $wp_party_table;?>"/>
        </div>
        <?php 
        //after main form elementst hook
        do_action('wp_party_admin_form_end'); 
        ?>
        </div>
        <?php
    }
    public function prepend_party_meta_to_content($content){

        global $post, $post_type;
    
        //display meta only on our partys (and if its a single party)
        if($post_type == 'wp_parties' && is_singular('wp_parties')){
    
            //collect variables
            $wp_party_id = $post->ID;
            $wp_party_GM = get_post_meta($post->ID,'wp_party_GM',true);
            $wp_party_ambiance = get_post_meta($post->ID,'wp_party_ambiance',true);
            $wp_party_univers = get_post_meta($post->ID,'wp_party_univers',true);
            $wp_party_pitch = get_post_meta($post->ID,'wp_party_pitch',true);
            $wp_party_language = get_post_meta($post->ID,'wp_party_language',true);
            $wp_party_time = get_post_meta($post->ID,'wp_party_time',true);
            $wp_party_players = get_post_meta($post->ID,'wp_party_players',true);
            $wp_party_table = get_post_meta($post->ID,'wp_party_table',true);

            //display
            $html = '';
    
            $html .= '<section class="meta-data">';
    
            //hook for outputting additional meta data (at the start of the form)
            do_action('wp_party_meta_data_output_start',$wp_party_id);
    
            $html .= '<p>';
            //
            if(!empty($wp_party_GM)){
                $html .= '<b>Le mj de la partie</b> ' . $wp_party_GM . '</br>';
            }
            //
            if(!empty($wp_party_ambiance)){
                $html .= '<b>L\'ambiance</b> ' . $wp_party_ambiance . '</br>';
            }
            //
            if(!empty($wp_party_univers)){
                $html .= '<b>L\'univers</b> ' . $wp_party_univers . '</br>';
            }
            //
            if(!empty($wp_party_pitch)){
                $html .= '<b>Quelques mots</b> ' . $wp_party_pitch . '</br>';
            }
            //
            if(!empty($wp_party_language)){
                $html .= '<b>la langue</b> ' . $wp_party_language . '</br>';
            }
            //
            if(!empty($wp_party_time)){
                $html .= '<b>Le temps de la partie</b> ' . $wp_party_time . '</br>';
            }
            //
            if(!empty($wp_party_players)){
                $html .= '<b>Le nombre de joueurs</b> ' . $wp_party_players . '</br>';
            }
            if(!empty($wp_party_table)){
                $html .= '<b>N° de table</b> ' . $wp_party_table . '</br>';
            }
            $html .= '</p>';
            //hook for outputting additional meta data (at the end of the form)
            do_action('wp_party_meta_data_output_end',$wp_party_id);
    
            $html .= '</section>';
            $html .= $content;
    
            return $html;  
    
    
        }else{
            return $content;
        }
    
    }
    //triggered when adding or editing a location
    public function save_party($post_id){

        //check for nonce
        if(!isset($_POST['wp_party_nonce_field'])){
            return $post_id;
        }   
        //verify nonce
        if(!wp_verify_nonce($_POST['wp_party_nonce_field'], 'wp_party_nonce')){
            return $post_id;
        }
        //check for autosave
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
            return $post_id;
        }
        
        $wp_party_GM = isset($_POST['wp_party_GM']) ? sanitize_text_field($_POST['wp_party_GM']) : '';
        $wp_party_ambiance = isset($_POST['wp_party_ambiance']) ? sanitize_text_field($_POST['wp_party_ambiance']) : '';
        $wp_party_univers = isset($_POST['wp_party_univers']) ? sanitize_text_field($_POST['wp_party_univers']) : '';
        $wp_party_pitch = isset($_POST['wp_party_pitch']) ? $_POST['wp_party_pitch'] : '';
        $wp_party_language = isset($_POST['wp_party_language']) ? sanitize_text_field($_POST['wp_party_language']) : '';
        $wp_party_time = isset($_POST['wp_party_time']) ? sanitize_text_field($_POST['wp_party_time']) : '';
        $wp_party_players = isset($_POST['wp_party_players']) ? sanitize_text_field($_POST['wp_party_players']) : '';
        $wp_party_table = isset($_POST['wp_party_table']) ? sanitize_text_field($_POST['wp_party_table']) : '';


        //update phone, memil and address fields
        update_post_meta($post_id, 'wp_party_GM', $wp_party_GM);
        update_post_meta($post_id, 'wp_party_ambiance', $wp_party_ambiance);
        update_post_meta($post_id, 'wp_party_univers', $wp_party_univers);
        update_post_meta($post_id, 'wp_party_pitch', $wp_party_pitch);
        update_post_meta($post_id, 'wp_party_language', $wp_party_language);
        update_post_meta($post_id, 'wp_party_time', $wp_party_time);
        update_post_meta($post_id, 'wp_party_players', $wp_party_players);
        update_post_meta($post_id, 'wp_party_table', $wp_party_table);



        //location save hook 
        //used so you can hook here and save additional post fields added via 'wp_location_meta_data_output_end' or 'wp_location_meta_data_output_end'
        do_action('wp_party_admin_save',$post_id, $_POST);

    }
    /*Category custom pour les parties de jeu de rôle, cela permettra de les triers plus facilement plus tard*/
    function add_custom_taxonomy() {
 
        $labels = array(
          'name' => _x( 'Types', 'taxonomy general name' ),
          'singular_name' => _x( 'Type', 'taxonomy singular name' ),
          'search_items' =>  __( 'Chercher départ' ),
          'all_items' => __( 'Tous les départs' ),
          'parent_item' => __( 'Parent Type' ),
          'parent_item_colon' => __( 'Parent Type:' ),
          'edit_item' => __( 'Modifier le départ' ), 
          'update_item' => __( 'Mettre à jour le départ' ),
          'add_new_item' => __( 'Ajouter un nouveau départ' ),
          'new_item_name' => __( 'Nouveau nom de départ' ),
          'menu_name' => __( 'Types' ),
        ); 	
       
        register_taxonomy('types',array('wp_parties'), array(
          'hierarchical' => true,
          'labels' => $labels,
          'query_var' => true,
          'rewrite' => array( 'slug' => 'type' ),
        ));
    }
    /**
     * Fonction d'enregistrement des paramètres pour l'extension des hydriades
     * 
     */
    function hydriade_register_settings() {
        add_option( 'Registration', '1');
        add_option( 'NameMail', 'hydriade@hydre.ch');
        register_setting( 'HydriadeOption', 'NameMail');
        register_setting( 'HydriadeOption', 'Registration');
    }
    /**
     * Ajout de la page pour la modification des options pour les hydriades
     * 
     */
    function hydriade_register_options_page() {
        add_options_page('Hydriade option', 'Hydriade', 'manage_options', 'hydriade_options', array($this,'hydriade_options_page'));
    }
    /**
     * Fonction d'affichage de la page des paramètres pour l'extension des hydriades
     * 
     */
    function hydriade_options_page(){
        
        ?>
        <div>
        <?php screen_icon(); ?>
        <h2>Option hydriade</h2>
        <form method="post" action="options.php">
        <?php settings_fields( 'HydriadeOption' ); ?>
        <h3>Page d'option pour les hydriades</h3>
        <table>
        <tr valign="top">
        <th scope="row"><label for="NameMail">Mail automatique pour les hydriades</label></th>
        <td><input type="text" id="NameMail" name="NameMail" value="<?php echo get_option('NameMail'); ?>" /></td>
        </tr>
        <th scope="row"><label for="NameMail">Inscription différé</label></th>
        <?php
        $text = array(
            'Text',
            'Personne ne peut s\'inscrire',
            'Que les MJs peuvent proposer des parties',
            'Joueurs et MJs peuvent proposer et s\'inscrire à des parties',
        );
        for($i = 1; $i <= 3; $i++){
            if(get_option('Registration') == $i){
                echo'<tr><td>'.$text[$i].'</td><td><input type="radio" id="Registration" name="Registration" value="'.get_option('Registration').'"  checked></td></tr>';
            }
            else{
                echo'<tr><td>'.$text[$i].'</td><td><input type="radio" id="Registration" name="Registration" value="'.$i.'"></td></tr>';
            }
        }
        ?>
        </table>
        <?php  submit_button(); ?>
        </form>
        </div>
        <?php
        
    }
    public function plugin_activate(){  
        //call our custom content type function
        $this->register_parties();
        //flush permalinks
        flush_rewrite_rules();
    }
    public function enqueue_public_scripts_and_styles(){
        wp_enqueue_style('wp_hydriades_public_styles', plugin_dir_url(__FILE__).'CSS/wp_hydriades_public-css.css',array(), '1.0', 'all');
        wp_enqueue_script( 'wp_hydriade_public_js', plugins_url( 'js/wp_hydriade_public_js.js', __FILE__ ), array('jquery') );
        wp_localize_script( 'wp_hydriade_public_js', 'frontend_ajax_object',array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
        wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js', array(), null, true);
    }
    public function enqueue_admin_scripts_and_styles(){
        wp_enqueue_style('wp_hydriades_public_styles', plugin_dir_url(__FILE__).'CSS/wp_hydriades_public-css.css',array(), '1.0', 'all');
    }
    //trigered on deactivation of the plugin (called only once)
    public function plugin_deactivate(){
        //flush permalinks
        flush_rewrite_rules();
    }
}
include(plugin_dir_path(__FILE__) . 'inc/wp_hydriades_shortcodes.php');

include(plugin_dir_path(__FILE__) . 'TCPDF-master/tcpdf.php');

$wp_hydriade = new wp_hydriade;

/**
 * classes pour les PDFs
 */
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetTextColor(167, 147, 68);
    }
    public function Footer() {
        $this->SetY(-10);
        $this->SetFont('helvetica', 'B', 8);
    }
}
?>