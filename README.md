# WordPress Plugin for MarkLogic Search

Very much work in progress.  For more info, hit me up [on twitter](http://twitter.com/eedeebee)

### Dependencies

* PHP 3.5+ in a web server (I've only tried Apache 2.2).
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

#### Roadmap

* save_post hook to store contents in DB
* admin button to reload all 
* Basic search results for text-based post-types (pre_get_posts/the_posts hooks)
* Metadata, Dates, Authors, Tags, Categories, Taxonomies
* Design for attachments
* Hit highlighting results?
* Figure out how facets work

