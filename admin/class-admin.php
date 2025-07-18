<?php
/**
 * Admin interface class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Admin {
    
    private $database;
    
    public function __construct() {
        $this->database = new KotacomAI_Database();
        $this->init();
    }
    
    /**
     * Initialize admin interface
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Kotacom AI', 'kotacom-ai'),
            __('Kotacom AI', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai',
            array($this, 'display_generator_page'),
            'dashicons-robot',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'kotacom-ai',
            __('Content Generator', 'kotacom-ai'),
            __('Generator', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai',
            array($this, 'display_generator_page')
        );
        
        add_submenu_page(
            'kotacom-ai',
            __('Keywords Management', 'kotacom-ai'),
            __('Keywords', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-keywords',
            array($this, 'display_keywords_page')
        );
        
        add_submenu_page(
            'kotacom-ai',
            __('Prompt Templates', 'kotacom-ai'),
            __('Prompts', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-prompts',
            array($this, 'display_prompts_page')
        );

        // NEW: Template Editor Submenu
        add_submenu_page(
            'kotacom-ai',
            __('Template Editor', 'kotacom-ai'),
            __('Templates', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-templates', // Unique slug for the template editor page
            array($this, 'display_template_editor_page') // New method to display the page
        );
        
        add_submenu_page(
            'kotacom-ai',
            __('Queue Status', 'kotacom-ai'),
            __('Queue', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-queue',
            array($this, 'display_queue_page')
        );
        
        add_submenu_page(
            'kotacom-ai',
            __('Settings', 'kotacom-ai'),
            __('Settings', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-settings',
            array($this, 'display_settings_page')
        );
        
        add_submenu_page(
            'kotacom-ai',
            __('Generator Post Template', 'kotacom-ai'),
            __('Generator Post Template', 'kotacom-ai'),
            'manage_options',
            'kotacom-ai-generator-post-template',
            array(
                $this,
                'display_generator_post_template_page'
            )
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only enqueue for Kotacom AI admin pages
        if (strpos($hook, 'kotacom-ai') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('jquery-ui-sortable'); // Required for drag & drop in template editor
        wp_enqueue_script('jquery-ui-droppable'); // Required for drag & drop in template editor
        
        wp_enqueue_script(
            'kotacom-ai-admin',
            KOTACOM_AI_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-autocomplete'),
            KOTACOM_AI_VERSION,
            true
        );
        
        wp_enqueue_style(
            'kotacom-ai-admin',
            KOTACOM_AI_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            KOTACOM_AI_VERSION
        );
        
        // Localize script (common for all Kotacom AI pages)
        wp_localize_script('kotacom-ai-admin', 'kotacomAI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kotacom_ai_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'kotacom-ai'),
                'processing' => __('Processing...', 'kotacom-ai'),
                'error' => __('An error occurred. Please try again.', 'kotacom-ai'),
                'success' => __('Operation completed successfully.', 'kotacom-ai'),
                'required_field' => __('This field is required.', 'kotacom-ai'),
                'validation_error' => __('Please fill in all required fields.', 'kotacom-ai'),
                'permission_error' => __('Permission denied.', 'kotacom-ai'),
                'server_error' => __('Server error occurred.', 'kotacom-ai'),
                'network_error' => __('Network error occurred.', 'kotacom-ai')
            ),
            'settingsUrl' => admin_url('admin.php?page=kotacom-ai-settings') // Pass settings URL for configure button
        ));

        // Enqueue template editor specific script only on its page
        if ($hook === 'kotacom-ai_page_kotacom-ai-templates') {
            wp_enqueue_script(
                'kotacom-ai-template-editor',
                KOTACOM_AI_PLUGIN_URL . 'admin/js/template-editor.js',
                array('jquery', 'jquery-ui-sortable', 'jquery-ui-droppable', 'kotacom-ai-admin'), // Ensure admin.js is loaded first
                KOTACOM_AI_VERSION,
                true
            );
        }
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // API Settings - Google AI
        register_setting('kotacom_ai_settings', 'kotacom_ai_api_provider');
        register_setting('kotacom_ai_settings', 'kotacom_ai_google_ai_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_google_ai_model');
        
        // API Settings - OpenAI
        register_setting('kotacom_ai_settings', 'kotacom_ai_openai_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_openai_model');
        
        // API Settings - Groq
        register_setting('kotacom_ai_settings', 'kotacom_ai_groq_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_groq_model');
        
        // API Settings - Cohere
        register_setting('kotacom_ai_settings', 'kotacom_ai_cohere_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_cohere_model');
        
        // API Settings - Hugging Face
        register_setting('kotacom_ai_settings', 'kotacom_ai_huggingface_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_huggingface_model');
        
        // API Settings - Together AI
        register_setting('kotacom_ai_settings', 'kotacom_ai_together_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_together_model');
        
        // API Settings - Anthropic
        register_setting('kotacom_ai_settings', 'kotacom_ai_anthropic_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_anthropic_model');
        
        // API Settings - Replicate
        register_setting('kotacom_ai_settings', 'kotacom_ai_replicate_api_key');
        register_setting('kotacom_ai_settings', 'kotacom_ai_replicate_model');
        
        // Default Parameters
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_tone');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_length');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_audience');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_post_type');
        register_setting('kotacom_ai_settings', 'kotacom_ai_default_post_status');
        
        // Queue Settings
        register_setting('kotacom_ai_settings', 'kotacom_ai_queue_batch_size');
        register_setting('kotacom_ai_settings', 'kotacom_ai_queue_processing_interval');
    }
    
    /**
     * Display content generator page
     */
    public function display_generator_page() {
        $prompts = $this->database->get_prompts();
        $tags = $this->database->get_all_tags();
        $categories = get_categories(array('hide_empty' => false));
        $post_types = get_post_types(array('public' => true), 'objects');
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/generator.php';
    }
    
    /**
     * Display keywords management page
     */
    public function display_keywords_page() {
        $tags = $this->database->get_all_tags();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/keywords.php';
    }
    
    /**
     * Display prompts management page
     */
    public function display_prompts_page() {
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/prompts.php';
    }

    /**
     * NEW: Display template editor page
     */
    public function display_template_editor_page() {
        // You might need to pass data to the template editor view, e.g., existing templates
        // $templates = $this->database->get_templates(); // Assuming a method exists
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/template-editor.php';
    }
    
    /**
     * Display queue status page
     */
    public function display_queue_page() {
        $queue_status = $this->database->get_queue_status();
        $failed_items = $this->database->get_failed_queue_items();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/queue.php';
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        $api_handler = new KotacomAI_API_Handler();
        $providers = $api_handler->get_providers();
        
        include KOTACOM_AI_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Display Generator Post Template page
     */
    public function display_generator_post_template_page() {
        include plugin_dir_path(__FILE__) . 'views/generator-post-template.php';
    }
}
