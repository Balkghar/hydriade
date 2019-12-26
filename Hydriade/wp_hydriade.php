<?php
/**
 * Plugin Name:       Hydriade
 * Plugin URI:        -
 * Description:       Plugin pour gérer les évenements des hydriades
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Hugo Germano
 * Author URI:        -
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */
class wp_hydriade{
    //fonction qui s'éxecute lorsque la classe est créé
    public function __construct(){
        add_action('admin_menu', array($this,'custom_menu'));
        add_action('init', array($this, 'register_parties'));
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
    }
    function admin_hydriade(){
        echo "<h1>Hydriade</h1>";
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
            'supports'          => array('title'),
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
        add_submenu_page('hydriade', 'Ajouter un départ', 'Ajouter un départ', 'edit_posts', 'edit-tags.php?taxonomy=types',false );
      }
    public function plugin_activate(){  
        //call our custom content type function
        $this->register_parties();
        //flush permalinks
        flush_rewrite_rules();
    }
    public function enqueue_public_scripts_and_styles(){
        wp_enqueue_style('wp_hydriades_public_styles', plugin_dir_url(__FILE__). '/css/wp_hydriades_public-css.css');
        wp_enqueue_script( 'wp_hydriade_public_js.js', plugins_url( '/js/wp_hydriade_public_js.js', __FILE__ ), array('jquery') );
    
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