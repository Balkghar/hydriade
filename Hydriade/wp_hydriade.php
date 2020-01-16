<?php
/**
 * Plugin Name:       Hydriade
 * Plugin URI:        -
 * Description:       Plugin pour gérer les évenements des hydriades
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Hugo Germano & Esteban Lopez
 * Author URI:        -
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
        add_action('add_meta_boxes', array($this,'add_party_meta_boxes')); //add meta boxes
        add_filter('the_content', array($this,'prepend_party_meta_to_content')); //gets our meta data and dispayed it before the content
        add_action('save_post_wp_parties', array($this,'save_party')); //save party
        add_action( 'init', array($this,'add_custom_taxonomy'), 0 );
        add_action('wp_enqueue_scripts', array($this,'enqueue_public_scripts_and_styles')); //public scripts and styles
        register_activation_hook(__FILE__, array($this,'plugin_activate')); //activate hook
        register_deactivation_hook(__FILE__, array($this,'plugin_deactivate')); //deactivate hook
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

    }
    /**Page d'accueil de l'extension */
    function admin_hydriade(){
        echo "<h1>Hydriade</h1>";
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
        if(!empty($_POST['userIDInscr']) && !empty($_POST['postIDInscr'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDInscr']))) && get_post(esc_attr(strip_tags($_POST['postIDInscr'])))){
                update_user_meta($_POST['userIDInscr'], 'Party'.$_POST['postIDInscr'], 'Registered');
            }
        }
        if(!empty($_POST['userIDRefu']) && !empty($_POST['postIDRefu'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDRefu']))) && get_post(esc_attr(strip_tags($_POST['postIDRefu'])))){
                delete_user_meta($_POST['userIDRefu'], 'Party'.$_POST['postIDRefu'], 'registeredWait');
            }
        }
        if(!empty($_POST['userIDDe']) && !empty($_POST['postIDDe'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDDe']))) && get_post(esc_attr(strip_tags($_POST['postIDDe'])))){
                delete_user_meta($_POST['userIDDe'], 'Party'.$_POST['postIDDe'], 'Registered');
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
        if(!empty($_POST['userIDGM'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDGM'])))){
                update_user_meta($_POST['userIDGM'], 'hydRole', 'GM');
            }
        }
        if(!empty($_POST['userIDPL'])){
            if(get_userdata(esc_attr(strip_tags($_POST['userIDPL'])))){
                update_user_meta($_POST['userIDPL'], 'hydRole', 'PL');
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

    ?>
    <p>Entrer les informations de votre partie</p>
    <div class="field-container">
        <?php 
        //before main form elementst hook
        do_action('wp_party_admin_form_start'); 
        ?>
        <div class="field">
            <label for="wp_party_GM">Le maître de jeu</label>
            <input type="text" name="wp_party_GM" id="wp_party_GM" value="<?php echo $wp_party_GM;?>"/>
        </div>
        <div class="field">
            <label for="wp_party_ambiance">L'ambiance</label>
            <input type="text" name="wp_party_ambiance" id="wp_party_ambiance" value="<?php echo $wp_party_ambiance;?>"/>
        </div>
        <div class="field">
            <label for="wp_party_univers">L'univers</label>
            <input type="text" name="wp_party_univers" id="wp_party_univers" value="<?php echo $wp_party_univers;?>"/>
        </div>
        <div class="field">
            <label for="wp_party_pitch">Quelques mots</label>
            <textarea name="wp_party_pitch" id="wp_party_pitch"><?php echo $wp_party_pitch;?></textarea>
            <!--<input type="textarea" name="wp_party_pitch" id="wp_party_pitch" value="<?php echo $wp_party_pitch;?>"/>-->
        </div>
        <?php
        $Languages = array(
            "french" => "Français",
            "english" => "Anglais",
            "deutsch" => "Allemand",
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
        for($i = 1; $i <= 10; $i++){
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

        //get our phone, email and address fields
        $wp_party_GM = isset($_POST['wp_party_GM']) ? sanitize_text_field($_POST['wp_party_GM']) : '';
        $wp_party_ambiance = isset($_POST['wp_party_ambiance']) ? sanitize_text_field($_POST['wp_party_ambiance']) : '';
        $wp_party_univers = isset($_POST['wp_party_univers']) ? sanitize_text_field($_POST['wp_party_univers']) : '';
        $wp_party_pitch = isset($_POST['wp_party_pitch']) ? sanitize_text_field($_POST['wp_party_pitch']) : '';
        $wp_party_language = isset($_POST['wp_party_language']) ? sanitize_text_field($_POST['wp_party_language']) : '';
        $wp_party_time = isset($_POST['wp_party_time']) ? sanitize_text_field($_POST['wp_party_time']) : '';
        $wp_party_players = isset($_POST['wp_party_players']) ? sanitize_text_field($_POST['wp_party_players']) : '';

        //update phone, memil and address fields
        update_post_meta($post_id, 'wp_party_GM', $wp_party_GM);
        update_post_meta($post_id, 'wp_party_ambiance', $wp_party_ambiance);
        update_post_meta($post_id, 'wp_party_univers', $wp_party_univers);
        update_post_meta($post_id, 'wp_party_pitch', $wp_party_pitch);
        update_post_meta($post_id, 'wp_party_language', $wp_party_language);
        update_post_meta($post_id, 'wp_party_time', $wp_party_time);
        update_post_meta($post_id, 'wp_party_players', $wp_party_players);


        //location save hook 
        //used so you can hook here and save additional post fields added via 'wp_location_meta_data_output_end' or 'wp_location_meta_data_output_end'
        do_action('wp_party_admin_save',$post_id, $_POST);

    }
    /*Category custom pour les parties de jeu de rôle, cela permettra de les triers plus facilement plus atrd*/
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
        add_submenu_page('hydriade', 'Ajouter un départ', 'Ajouter un départ', 'edit_posts', 'edit-tags.php?taxonomy=types',false);
    }
    public function plugin_activate(){  
        //call our custom content type function
        $this->register_parties();
        //flush permalinks
        flush_rewrite_rules();
    }
    public function enqueue_public_scripts_and_styles(){
        wp_enqueue_style('wp_hydriades_public_styles', plugin_dir_url(__FILE__).'CSS/wp_hydriades_public-css.css',array(), '1.0', 'all');
        wp_enqueue_script( 'wp_hydriade_public_js.js', plugins_url( 'js/wp_hydriade_public_js.js', __FILE__ ), array('jquery') );
    
    }
    //trigered on deactivation of the plugin (called only once)
    public function plugin_deactivate(){
        //flush permalinks
        flush_rewrite_rules();
    }
}
include(plugin_dir_path(__FILE__) . 'inc/wp_hydriades_shortcodes.php');
$wp_hydriade = new wp_hydriade;
?>