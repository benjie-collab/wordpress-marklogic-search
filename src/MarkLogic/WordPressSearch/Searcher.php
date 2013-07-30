<?php
namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

class Searcher{

    static function esc($s) {
        return htmlentities($s, ENT_COMPAT, "UTF-8");
    }

    static public $constraints = array(
        'content-tidy' => "content",
        'title'        => "title",
        'name'         => "url",
        "display_name" => "author"
    );
    
    public function query($search, $pageIndex, $size, $facets = array()) {

        $options = new MLPHP\Options(Api::client());

        foreach (self::$constraints as $elt => $name) {
            $constraint = new MLPHP\WordConstraint($name, $elt, "", null, null);
            $constraint->setTermOptions(array(
                'wildcarded'
            ));
            $options->addConstraint($constraint);
        }

        $field_constraint = new MLPHP\FieldWordConstraint("default", "content01");

        $term = new MLPHP\Term('all-results');
        $term->setTermOptions(array(
            'wildcarded'
        ));

        // $term->setDefault($content_constraint);  /* Can use this when you don't have the DB field named content 'content01' defined */
        $term->setDefault($field_constraint); 
        $options->setTerm($term);


        try {
            $options->write("wms");
            Api::logger()->debug("Wrote wms options");
        } catch (\Exception $ex) {
            Api::logger()->error($ex);
            return null;
        }
            
        $query = new MLPHP\Search(Api::client(), $pageIndex * $size, $size);

        //Possibility to modify the query after it was built
        \apply_filters('mlphp_query', $query);

        $results = null;

        try {
            $results = $query->retrieve($search, array(
                'options' => 'wms' 
            ));
        } catch (\Exception $ex) {
            Api::logger()->error($ex);
            return null;
        }

        
        /*
        try{
            $index = Api::index(false);

            $search = new \Elastica_Search($index->getClient());
            $search->addIndex($index);

            if($bytype){
                $search->addType($index->getType($bytype));
            }

            \apply_filters( 'elastica_pre_search', $search );

            $response = $search->search($query);
        }catch(\Exception $ex){
            return null;
        }
        */

        $ids = array();
        $scores = array();
        $snippets = array();
        $facets = array();

        foreach ($results->getResults() as $result) {
            $id = Document::uri_to_id($result->getURI());
            $ids[] = $id;
            $scores[$id] = $result->getScore();
            $snippets[$id] = array();
            foreach ($result->getMatches() as $match) {
                $snippets[$id][] = $match;
            }
        }

        Api::logger()->debug("Total : " . $results->getTotal());

        $val = array(
            'total' => $results->getTotal(),
            'snippets' => $snippets,
            'scores' => $scores,
            'facets' => $facets,
            'ids' => $ids
        );

        return \apply_filters('marklogic_search_results', $val, $results);
    }

    public function highlight($search, $contentType, $content) {
        $query = new MLPHP\Search(Api::client());
        return $query->highlight($content, $contentType, "hit", $search);
    }

}

