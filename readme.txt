=== WDS Custom Login Page ===
Contributors: jazzs3quence, webdevstudios
Tags: login page, login
Requires at least: 2.7
Tested up to: 4.4
Stable tag: 1.1

Create a custom login page on your WordPress site.

== Description ==

= Usage =
On plugin activation, Custom Login Page looks for a page with the slug 'login'. If this page doesn't exist, it will create it. If *nothing* else is done at this point, the plugin will work perfectly. It uses a filter to display the login form on a page named 'login' and all requests for wp-login.php will redirect to that login page.

But maybe you want to use a custom template to handle your login page. That works, too, and there are a couple ways you can do it.

*Option 1: Using a `page-*.php` template*
Let's say you want to create a page template that WordPress knows right off the bat will be the template used for a particular page. The way you'd do this in WordPress is to name your page template `page-*.php`, where the `*` is the slug of the page. In our case, maybe we have a page template named `page-login.php`. This template will be used *by default* by the plugin if the file exists.

But what about if I don't want to use the 'login' slug for my login page? That's okay, you can name it whatever you want. The only difference is that, if you change the slug, you'll need to hit the Login Page Options and set the page you want to use as the login page. That will tell the plugin not to default to `login` for the page slug and, instead use whatever the slug is for the page you set.

*Option 2: Using a custom page template*
What if you don't want to use a `page-*.php` template and you want to name your custom template something else. In this case, you'll need to use the `template-*.php` naming convention. By default -- just like with the `page-*.php` template -- the plugin will look for a `template-login.php` file and use that, if it exists. If it doesn't, you'll need to set it on the options page, just like you did in Option 1. Also like Option 1, the naming convention needs to match -- whatever your page slug is for your login page, needs to be the same for the `*` in the `template-*.php` -- so if you changed your slug to `login-page`, you'll need to have a template named `template-login-page.php`.

In either case, you need to actually add in the login form. The form *won't* be added automatically for you if you are using a custom template file.

*Manually inserting the login form*
Once again, there are 2 ways to add the login form to your login page if you're using a custom template and not letting the Custom Login Page plugin do it for you.

*Using the template tag*
Add this function somewhere in your login page's custom template file:

`wds_login_form( $redirect, $echo )`

`wds_login_form` takes two optional parameters.

`$redirect`
`$redirect` is a custom url to a page you want to redirect the user to after they login. By default, it will try to redirect the user to the page they came from last. The default value is `''`.

`$echo`
`$echo` tells the plugin whether to echo the login form or just return it. It defaults to `false` and just returns the form. If you want to display it directly on a page without having to echo it (and you were setting the redirect to the default), you might use it like this:

`<?php wds_login_form( '', true ); ?>`

*Using the shortcode*
Did I mention there's a shortcode? You can also not worry about all this coding stuff and just insert a login form shortcode in the body of your login page's content. The shortcode is really simple:

`[login_form]`

The shortcode takes no parameters and just uses the default behavior for redirection.

== Installation ==

1. Upload the `wds-custom-login-page` directory to the `/wp-content/plugins/` directory or add via the WordPress plugin installer.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php wds_login_form( $redirect, $echo ); ?>` in your templates or use one of the other methods described in the Usage section.

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 1.1 =
* Updated how CMB2 is required
* Use the internal cmb2_get_option wrapper to get the login page slug. get_option was being used but wrong option name was passed.
* Created new wrapper function to independently a login message. Can be used if the wds_login_form function is invoked manually.

= 1.0 =
* Initial Release

== Upgrade Notice ==

