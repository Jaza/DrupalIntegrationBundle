Symfony2 Drupal Integration Bundle
==================================

This bundle lets you render a Symfony2 web app via Drupal 7. It also
provides helpers for performing access control on a Symfony route,
based on Drupal authentication and Drupal user roles. Plus a few other
Symfony-Drupal integration helper methods.

## Installation

1  Add to the 'require' section of composer.json:  

``` 
    "require" : {
        "jaza/drupal-integration-bundle": "1.0.*@dev",
    }
``` 

2  Register the bundle in ``app/AppKernel.php``

``` php
    $bundles = array(
        // ...
        new Jaza\DrupalIntegrationBundle\JazaDrupalIntegrationBundle(),
    );
```

## Configuration

1  Add required config values to 'parameters.yml' file (or equivalent):

``` 
parameters:
    # ...
    drupal_root: /path/to/drupal
    drupal_base_url: 'http://drupal.baseurl'
```

2  Add various optional config values to 'parameters.yml' file
   (or equivalent):

``` 
parameters:
    # ...
    drupal_is_embedded: true
    drupal_menu_active_item: node/123
    drupal_wysiwyg_input_format_id: full_html
    drupal_css_includes: [/path/to/file1.css, /path/to/file2.css]
    drupal_get_author_username_methodname: getAuthorUsername
    drupal_get_author_uid_methodname: getAuthorUid
    drupal_admin_roles: [administrator]
    drupal_homelink_route: foo__default_index
    drupal_homelink_text: 'Foo home'
```

## Usage

Create a controller in your bundle

``` php

namespace YOURNAME\YOURBUNDLE\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction()
    {
        $title = 'Foo hoo';

        // Get the Drupal integration service and bootstrap Drupal
        $drupalIntegration = $this->container->get('drupal_integration');
        $drupalIntegration->bootstrapDrupal();

        // Throw a Symfony AccessDeniedException if the current
        // Drupal user is not logged-in.
        $drupalIntegration->limitAccessToAuthenticatedUsers();

        // Easily check if the current response will be rendered via
        // Drupal or not - e.g. might have conditional template logic
        // based on this.
        $embedded = $this->container->getParameter('drupal_is_embedded');

        // Easily check if the current user is a Drupal administrator
        // or not - e.g. might show an 'admin only' link in the
        // template based on this.
        $isAdmin = $drupalIntegration->isAdmin();

        $engine = $this->container->get('templating');

        // Render the Symfony template output and store it in a variable
        // here, rather than returning the response directly to
        // Symfony as you'd normally do.
        $content = $engine->render('FooBundle:Default:index.html.twig', array(
            'title' => $title,
            'embedded' => $embedded,
            'is_admin' => $isAdmin,
        ));

        // Return a Symfony Response object - whether the content in
        // the response is output via Drupal or not depends on the
        // 'drupal_is_embedded' config value.
        return $drupalIntegration->getResponse($content);
    }

    public function adminAction()
    {
        $title = 'Administration';

        $drupalIntegration = $this->container->get('drupal_integration');
        $drupalIntegration->bootstrapDrupal();
        $drupalIntegration->limitAccessToAuthenticatedUsers();

        // Throw a Symfony AccessDeniedException if the current
        // Drupal user is not a Drupal administrator.
        $drupalIntegration->limitAccessToAdmin();

        // Load the WYSIWYG library configured by the Drupal Wysiwyg
        // module (must be enabled if using this).
        // Enable WYSIWYG for the textarea with the specified ID.
        $drupalIntegration->loadWysiwyg('foo_bundle_textblocktype_content');

        // You'll need to output this in a hidden field in the template,
        // next to the textarea, emulating Drupal's WYSIWYG / input
        // formats behavior.
        $inputFormatId = $drupalIntegration->getWysiwygInputFormatId();

        // Get a rendered link to this app's Symfony front page.
        // Useful for e.g. displaying a breadcrumb throughout
        // the Symfony app.
        $homeLink = $drupalIntegration->getHomeLink();

        $engine = $this->container->get('templating');

        $content = $engine->render('FooBundle:Default:admin.html.twig', array(
            'title' => $title,
            'home_breadcrumb' => $homeLink,
            'input_format_id' => $inputFormatId,
        ));

        return $drupalIntegration->getResponse($content);
    }

    public function viewAction(FooModel $fooModel)
    {
        $title = $fooModel->getTitle();

        $drupalIntegration = $this->container->get('drupal_integration');
        $drupalIntegration->bootstrapDrupal();
        $drupalIntegration->limitAccessToAuthenticatedUsers();

        // Throw a Symfony AccessDeniedException if the current
        // Drupal user is not a Drupal administrator or the
        // author of the specified object.
        $drupalIntegration->limitAccessToAdminOrAuthor($fooModel);

        $engine = $this->container->get('templating');

        $content = $engine->render('FooBundle:Default:view.html.twig', array(
            'title' => $title,
        ));

        return $drupalIntegration->getResponse($content);
    }
}

```
