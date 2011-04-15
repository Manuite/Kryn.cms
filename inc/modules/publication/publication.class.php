<?php

class publication extends baseModule {
    


    public static function newsDetailFixed( $pConf ){
        
        $_REQUEST['e2'] = $pConf['news_rsn'];
        require_once( 'inc/modules/publication/publicationNews.class.php');
        return publicationNews::itemDetail( $pConf );
    }
    
    public function newsList( $pConf ){
    	// Check if RSS is requested
    	if($pConf['enableRss'] && getArgv('publication_rss')+0 == 1)
    		self::rssList($pConf); // rssList calls die(), no return needed
    	
        require_once( 'inc/modules/publication/publicationNews.class.php');
        return publicationNews::itemList( $pConf );
    }

    public function newsDetail( $pConf ){
        require_once( 'inc/modules/publication/publicationNews.class.php');
        return publicationNews::itemDetail( $pConf );
    }


    public function categoryList( $pConf ){
                
        $categories = implode($pConf['category_rsn'], ",");
        tAssign('pConf', $pConf);
        
        if( count($categories) > 0 )
            $where = " category_rsn IN ($categories) AND ";
        
        $sqlCount = "SELECT MAX(c.rsn) as rsn, MAX(c.title) as title, count(n.rsn) as count
            FROM %pfx%publication_news n, %pfx%publication_news_category c
            WHERE
             n.category_rsn = c.rsn AND deactivate = 0 GROUP BY category_rsn";
        $categoriesItems = dbExfetch( $sqlCount, -1 );
        tAssign('categories', $categoriesItems);
        
        return tFetch('publication/categoryList/'.$pConf['template'].'.tpl');
    
    }
    
    public function getOrderOptions( $pFields ){
        $array['title'] = _l('Title');
        $array['releaseat'] = _l('Release date');
        $array['releasedate'] = _l('News date');
        $array['category_rsn'] = _l('Category');
        return $array;
    }
    
     public function getOrderDirectionOptions( $pFields ){
        $array['desc'] = _l('Descending');
        $array['asc'] = _l('Ascending');
        return $array;
    }
    
    
	public function rssList( $pConf )
	{
		// Fetch important vars from conf var
		$categoryRsn = $pConf['category_rsn'];
		$itemsPerPage = $pConf['itemsPerPage']+0; // Make sure it's set
		$template = $pConf['rssTemplate'];
    	
		// Create category where clause
		$whereCategories = "";
		if(count($categoryRsn))
			$whereCategories = "AND n.category_rsn IN (".implode($categoryRsn, ",").") ";
		
		// Set items per page to default when not set
		if($itemsPerPage < 1)
			$itemsPerPage = 10; // Default
		
		// Create query
		$now = time();
		$sql = "
			SELECT 
				n.*, 
				c.title as categoryTitle 
			FROM 
				%pfx%publication_news n, 
				%pfx%publication_news_category c 
			WHERE
				    1=1
				$whereCategories 
				AND n.deactivate = 0
				AND n.category_rsn = c.rsn
				AND (n.releaseAt = 0 OR n.releaseAt <= $now)
			ORDER BY 
				releaseDate DESC 
			LIMIT $itemsPerPage";           
		
		$list = dbExFetch($sql, DB_FETCH_ALL);
		
		$hasItems = $list !== false;
		tAssign('hasItems', $hasItems); // Tells template if the query failed or not
		
		if($hasItems)
		{
			foreach($list as $index=>$item)
			{
				$list[$index]['title'] = strip_tags(html_entity_decode($item['title'], ENT_NOQUOTES, 'UTF-8'));
				
				$json = json_decode($item['intro'], true);
				if($json && $json['contents'] && file_exists('inc/template/'.$json['template']))
				{
					$oldContents = kryn::$contents;
					kryn::$contents = $json['contents'];
					$item['intro'] = tFetch($json['template']);
					kryn::$contents = $oldContents;
				}
				
				$list[$index]['intro'] = strip_tags(html_entity_decode($item['intro'], ENT_NOQUOTES, 'UTF-8'));
			}
		}
		
		// Assign list to template
		tAssign('items', $list);
		// Assign config to template
		tAssign('pConf', $pConf);
		
		// Clear current output
		@ob_end_clean();
		
		// Assign accept language to template
		tAssign('local', substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5));
		
		// Set header as XML
		header("Content-type: text/xml");
		
		// Ouput formatted XML list and die
		echo tFetch("publication/news/rss/$template.tpl");
		die();
    }

}


?>
