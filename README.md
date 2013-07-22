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

#### Roadmap

1. ~~save_post hook to store contents in DB~~
1. ~~admin button to reload all~~
1. ~~Basic search results for text-based post-types (pre_get_posts/the_posts hooks)~~
1. search_form Use http://stackoverflow.com/questions/1267044/css-for-text-background-in-text-input-element to display 
Powered by MarkLogic and also modifiy search_query to display current search string
1. Use the_excerpt hook to display hit highlighted snippets in place of excerpts - figure out what to do about html/text snippets
1. Use the_post hook to display hit highlighted content in place of regular content. (maybe same for author, title, etc).
1. Add support for comments and forum posts
1. Figure out how facets work in WP
    * For www, facet on 
        * user defined time buckets (last month, 1-6 mos, 6-12 mos, > 1 year ago) ?
        * Author?
        * Post type (blog post, forum post, others?)
1. Plugin cleanup/refactoring
1. Metadata, Dates, Authors, Tags, Categories, Taxonomies
1. Admin for specifying which posts types (and other stuff) should be indexed
1. Are there hooks for when metadata (authors, tags) change and update docs?
1. Design for attachments
1. Hit highlighting results?
1. Refactor admin (potentially) long running tasks (reload-all, clear-all) to be like f-e plugin (or other)

