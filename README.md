# <img width="125" height="78" src="http://s.wordpress.org/about/images/logos/wordpress-logo-stacked-rgb.png" alt="WordPress"/><font size="300">      ♥   </font><img style="margin-bottom:-8px" src="http://cdn.marklogic.com/content/themes/marklogic-v2/img/marklogic.png" alt="MarkLogic"/>


## A WordPress Plugin for MarkLogic Search

Very much work in progress.  Lots of things in flux.

For more info, hit me up [on twitter](http://twitter.com/eedeebee)

## Dependencies

* PHP 5.3+ in a web server (I've only tried Apache 2.2).
* WordPress 3.5+ or later (I've only tried 3.5)
* Composer

Only tried this on OS X so far, but I expect the plugin to work equally well on Linux and Windows.

Also, you should also install, if you don't have it, the [WordPress Session Manager Plugin](http://wordpress.org/plugins/wp-session-manager/)
plugin.  This plugin will work without that one, but you will get additional functionality with the Session Manager is available.
In particular, this plugin will generate hit-highlighted search result snippets.  When the Session Manager plugin is available, it will 
also hit highlight full posts based on the most recent search as well.


## Installation

1. Install the plugin in your WordPress plugin directory and use the WordPress admin UI to activate the plugin.
2. Install MarkLogic Server, create a database named `wordpress-01` and a REST API instance.  You can do this on your WordPress host or another host.  
Here is a [video of the installation](http://www.youtube.com/watch?feature=player_embedded&v=n4Oem-DsQaU)
(See also the text version [here](http://developer.marklogic.com/learn/rest/setup)).  Remember the `hostname` of the server
(you can use localhost if this is the same host as you WordPress server), the `port` for the REST API instance you create,
and the admin credentials (`user` and `password`) to access your server.
3. TBD:  Upload the marklogic-db.xml package to MarkLogic's configuration manager on port 8002.  This will configure database indexes for use
by the plugin.
4. Browse to the WordPress admin dashboard, select the MarkLogic Search plugin on the left hand side, and provide your MarkLogic Server
settings (`host`, `port`, `username`, `password`).  The use the "Test connection" button to verify your settings work and save them.
Also, click the checkbox to Enable the plugin and then hit 'Reload all' to cause copies of your WordPress content to be loaded into MarkLogic Server.
5.  Boom, you are ready to go.

## Issues

Please file issues at https://github.com/marklogic/wordpress-marklogic-search/issues

## Development details

To get going, pull down the repository and then

    % composer update

to pick up the plugin's dependencies.  You'll also need to 
[create yourself a MarkLogic Server database and REST API instance](http://www.youtube.com/watch?feature=player_embedded&v=n4Oem-DsQaU)
(See also the text version [here](http://developer.marklogic.com/learn/rest/setup)).

You can then install the plugin in WordPress and activate it.  This will get you an admin page
that you can use to set connection parameters based on your REST API instance.
For now, use your admin user credentials as I haven't worked out something more
fine grained yet.

## Roadmap

Strike-through means item is ~~Completed~~

### 1.0 

- Mostly replace built in WP search.
- Good enough for use on www.marklogic.com 


1. Setup/Install instructions 
2. ~~Admin UI for connection parameters, test, reload-all, clear, and disable/enable search.~~
3. Credentials stored encrypted in WP
4. Search input field with support for syntax for
    - ~~author~~
    - tag
    - category
    - ~~title~~
5. Search covers
    - content
    - author (display\_name only)
    - URI (maybe name (slug) only?)
    - tag
    - category
6. Search results rendered with hit-highlighted snippets (replacing normal WP Excerpts)
    - Consider special rendering for hits in places other than content
7. Consider hit highlighting documents displayed after a search (like developer.marklogic.com)
8. Document updates, edits, deletes update content in MarkLogic

### 1.1 

- Create something worth promotin. 
- Provide some additional benefits and fully replaces buildin WP search.


0. Catch events that cause edits to denormalized data (like Author, Tag, Category, Taxonomies, Metadata)
0. Setup/Install instructions video
0. Special rendering of search hits for places other than content (like author, title, URL, etc)
0. Facets
    - Tag/Category
    - Other Taxonomies
    - Metadata (admin config?)
    Including DB configs
0. Hit-highlighted content 

### 1.2 

- Add more functionality

0. Attachments
1. Search term completion or suggestion

### 2.0 

- Sufficient to support integrated www.marklogic.com, developer.marklogic.com, and docs.marklogic.com
More...

0. Support for returning search hits and facets for non-WP content in MarkLogic.
0. Storing search strings in MarkLogic for search analytics

## License

Original code in this plugin, like WordPress itself, is licensed via GPL v2.
