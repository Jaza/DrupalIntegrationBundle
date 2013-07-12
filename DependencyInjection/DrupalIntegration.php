<?php

namespace Jaza\DrupalIntegrationBundle\DependencyInjection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Router;

class DrupalIntegration
{
    private $request;

    private $router;

    private $drupal_root;

    private $drupal_base_url;

    private $drupal_is_embedded;

    private $drupal_menu_active_item;
    
    private $drupal_bootstrap_level;

    private $drupal_wysiwyg_input_format_id;

    private $drupal_css_includes;

    private $drupal_user;

    private $drupal_get_author_username_methodname;

    private $drupal_get_author_uid_methodname;

    private $drupal_admin_roles;

    private $drupal_homelink_route;

    private $drupal_homelink_text;

    public function __construct(Request $request, Router $router, $drupal_root, $drupal_base_url, $drupal_is_embedded, $drupal_menu_active_item, $drupal_wysiwyg_input_format_id = NULL, $drupal_css_includes = NULL, $drupal_get_author_username_methodname = NULL, $drupal_get_author_uid_methodname = NULL, $drupal_admin_roles = NULL, $drupal_homelink_route = NULL, $drupal_homelink_text = NULL)
    {
        $this->request = $request;
        $this->router = $router;
        $this->drupal_root = $drupal_root;
        $this->drupal_base_url = $drupal_base_url;
        $this->drupal_is_embedded = $drupal_is_embedded;
        $this->drupal_menu_active_item = $drupal_menu_active_item;
        $this->drupal_wysiwyg_input_format_id = $drupal_wysiwyg_input_format_id;
        $this->drupal_css_includes = $drupal_css_includes;
        $this->drupal_get_author_username_methodname = $drupal_get_author_username_methodname;
        $this->drupal_get_author_uid_methodname = $drupal_get_author_uid_methodname;
        $this->drupal_admin_roles = $drupal_admin_roles;
        $this->drupal_homelink_route = $drupal_homelink_route;
        $this->drupal_homelink_text = $drupal_homelink_text;
    }

    /**
     * Bootstraps Drupal using DRUPAL_ROOT and $base_url values from
     * this app's config. Bootstraps to a sufficient level to allow
     * session / user data to be accessed.
     * 
     * @param $level
     *   Level to bootstrap Drupal to. If not provided, defaults to
     *   DRUPAL_BOOTSTRAP_SESSION.
     */
    public function bootstrapDrupal($level = null)
    {
        global $base_url;
        global $user;

        // Check that Drupal bootstrap config settings can be found.
        // If not, throw an exception.
        if (empty($this->drupal_root)) {
            throw new \Exception('Missing setting \'drupal_root\' in config');
        }
        elseif (empty($this->drupal_base_url)) {
            throw new \Exception('Missing setting \'drupal_base_url\' in config');
        }

        // Set values necessary for Drupal bootstrap from external script. See:
        // http://www.csdesignco.com/content/using-drupal-data-functions-and-session-variables-external-php-script
        define('DRUPAL_ROOT', $this->drupal_root);
        $base_url = $this->drupal_base_url;

        // Bootstrap Drupal.
        chdir(DRUPAL_ROOT);
        require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
        if (is_null($level)) {
            if (!empty($this->drupal_is_embedded)) {
                $level = DRUPAL_BOOTSTRAP_FULL;
            }
            else {
                $level = DRUPAL_BOOTSTRAP_SESSION;
            }
        }

        if ($level == DRUPAL_BOOTSTRAP_FULL && !empty($this->drupal_menu_active_item)) {
            $_GET['q'] = $this->drupal_menu_active_item;
        }

        drupal_bootstrap($level);
        $this->drupal_bootstrap_level = $level;

        // Can't set this before bootstrapping Drupal
        // (e.g. would like to set it in the constructor of this class,
        // but it's not available at that time).
        $this->drupal_user = $user;

        if ($level == DRUPAL_BOOTSTRAP_FULL) {
            if (!empty($this->drupal_css_includes)) {
                foreach ($this->drupal_css_includes as $css_include) {
                    $css_include_filepath = sprintf('%s://%s%s%s', $this->request->getScheme(), $this->request->getHttpHost(), $this->request->getBasepath(), $css_include);

                    drupal_add_css($css_include_filepath, array('group' => CSS_THEME, 'type' => 'external'));
                }
            }
        }
    }

    /**
     * Gets a Symfony Response object for the specified content.
     * If set to embed output within Drupal, pass the content through
     * drupal_render_page() before preparing the Symfony Response object.
     *
     * @param $content
     *   Content string.
     *
     * @return
     *   Symfony Response object.
     */
    public function getResponse($content)
    {
        if ($this->drupal_is_embedded) {
            $content = drupal_render_page($content);
        }

        return new Response($content);
    }

    /**
     * Gets the current logged-in Drupal user object.
     *
     * @return
     *    Drupal user object.
     */
    public function getDrupalUser()
    {
        return $this->drupal_user;
    }

    /**
     * Checks that an authenticated and non-blocked Drupal user is tied to
     * the current session. If not, deny access for this request.
     */
    public function limitAccessToAuthenticatedUsers()
    {
        if (empty($this->drupal_user->uid)) {
            throw new AccessDeniedException('You must be logged in to access this page.');
        }
        if (empty($this->drupal_user->status)) {
            throw new AccessDeniedException('You must have an active account in order to access this page.');
        }
        if (empty($this->drupal_user->name)) {
            throw new AccessDeniedException('Your session must be tied to a username to access this page.');
        }
    }

    /**
     * Checks that the current user is either an admin,
     * or the author of the specified object. If not, deny access
     * for this request.
     */
    public function limitAccessToAdminOrAuthor($object)
    {
        if (!empty($this->drupal_get_author_username_methodname) && !empty($this->drupal_get_author_uid_methodname)) {
            $getAuthorUsername = $this->drupal_get_author_username_methodname;
            $getAuthorUid = $this->drupal_get_author_uid_methodname;
            if (!(
                ($object->$getAuthorUsername() == $this->drupal_user->name && $object->$getAuthorUid() == $this->drupal_user->uid) ||
                $this->isAdmin()
            )) {
                throw new AccessDeniedException('You must be an administrator or the object\'s author to access this page.');
            }
        }
    }

    /**
     * Checks that the current user is an admin.
     * If not, deny access for this request.
     */
    public function limitAccessToAdmin()
    {
        if (!$this->isAdmin()) {
            throw new AccessDeniedException('You must be an administrator to access this page.');
        }
    }

    /**
     * Checks if the current user is an admin.
     * 
     * @return
     *   TRUE if the user is admin, otherwise FALSE.
     */
    public function isAdmin()
    {
        $isAdmin = FALSE;

        if (!empty($this->drupal_admin_roles)) {
            foreach ($this->drupal_admin_roles as $adminRole) {
                if (in_array($adminRole, $this->drupal_user->roles)) {
                    $isAdmin = TRUE;
                    break;
                }
            }
        }

        return $isAdmin;
    }

    /**
     * Loads a Drupal WYSIWYG editor for the textarea specified.
     * 
     * Mostly copied from wysiwyg_pre_render_text_format() in
     * wysiwyg.module.
     * 
     * @param $field_id
     *   String of the CSS selector ID (minus the '#') of the textarea
     *   to make WYSIWYG.
     */
    public function loadWysiwyg($field_id)
    {
        if ($this->drupal_bootstrap_level && $this->drupal_bootstrap_level == DRUPAL_BOOTSTRAP_FULL && !empty($this->drupal_menu_active_item)) {
            drupal_add_js('misc/textarea.js');
            $format_id = $this->drupal_wysiwyg_input_format_id;

            $settings = array('field' => $field_id);
            $format = 'format' . $format_id;
            $format_key = $field_id . '-format--' . $format_id;
            $settings[$format] = array(
                'editor' => 'none',
                'status' => 1,
                'toggle' => 1,
                'resizable' => 1,
            );
            $profile = wysiwyg_get_profile($format_id);

            if ($profile) {
                $settings[$format]['editor'] = $profile->editor;

                $theme = wysiwyg_get_editor_themes($profile, (isset($profile->settings['theme']) ? $profile->settings['theme'] : ''));

                wysiwyg_add_plugin_settings($profile);
                wysiwyg_add_editor_settings($profile, $theme);
                drupal_add_js(array('wysiwyg' => array('triggers' => array($format_key => $settings))), 'setting');
            }
        }
    }

    /**
     * Gets the WYSIWYG input format ID configured.
     *
     * @return
     *   WYSIWYG input format ID string.
     */
    public function getWysiwygInputFormatId()
    {
        return $this->drupal_wysiwyg_input_format_id;
    }

    /**
     * Gets the home link.
     */
    public function getHomeLink()
    {
        if (!empty($this->drupal_homelink_route) && !empty($this->drupal_homelink_text)) {
            return '<a href="' . $this->router->generate($this->drupal_homelink_route) . '">' . $this->drupal_homelink_text . '</a>';
        }
    }
}
