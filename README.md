# WordPress Plugin for MarkLogic Search

Very much work in progress.  For more info, hit me up [on twitter](http://twitter.com/eedeebee)

### Dependencies

* PHP 5.3+ in a web server (I've only tried Apache 2.2).
* WordPress
* Composer
* MLPHP

### Development deets

To get going, pull down the repository and then

    % composer update

to pick up the plugin's dependencies.  You'll also need to 
[create yourself a MarkLogic Server database and REST API instance](http://www.youtube.com/watch?feature=player_embedded&v=n4Oem-DsQaU)
(See also the text version [here](http://developer.marklogic.com/learn/rest/setup)).

You can then install the plugin in WordPress and activate it.  This will get you an admin page
that you can use to set connection parameters based on your REST API instance.
For now, use your admin user credentials as I haven't worked out something more
fine grained yet.

Everything in flux.  More to come soon.  

### Roadmap

Strike-through means item is ~~Completed~~

#### 1.0 

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
(Also, perhaps use search\_form hook and technique described here: http://stackoverflow.com/questions/1267044/css-for-text-background-in-text-input-element to display Powered By)
5. Search covers
    - content
    - author (display_name only)
    - URI (maybe name (slug) only?)
    - tag
    - category
6. Search results rendered with hit-highlighted snippets (replacing normal WP Excerpts)
    - Consider special rendering for hits in places other than content
7. Consider hit highlighting documents displayed after a search (like developer.marklogic.com)
8. Document updates, edits, deletes update content in MarkLogic

#### 1.1 

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

#### 1.2 

- Add more functionality

0. Attachments
1. Search term completion

#### 2.0 

- Sufficient to support integrated www.marklogic.com, developer.marklogic.com, and docs.marklogic.com
More...

0. Support for returning search hits and facets for non-WP content in MarkLogic.
0. Storing search strings in MarkLogic for search analytics
