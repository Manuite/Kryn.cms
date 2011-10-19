<?php

class adminLayout {

    function init(){
        switch( getArgv(4) ){
        case 'get':
            return self::get( getArgv('name'), getArgv('plain') );

        case 'load': 
            return json(self::load(getArgv('type')));

        case 'save': 
            return json(self::save(getArgv('file')));

        case 'loadFile': 
            return json(self::loadFile(getArgv('file')));
        }
    }

    function save($pFile){
        $file = str_replace("..", "",$pFile);
        kryn::fileWrite("inc/template/$file", getArgv('content'));
        return true;
    }

    function loadFile( $pFile ){

        $res = array();
        foreach( kryn::$configs as $config ){
            if( $config['themes'] ){
                foreach( $config['themes'] as $themeTitle => $theme ){
                    foreach( $theme as $typeId => $typeItems ){
                        foreach( $typeItems as $title => $layout ){
                            if( $layout == $pFile ){
                                $res['title'] = $title;
                                $res['path'] = $layout;
                                $res['content'] = kryn::fileRead('inc/template/'.$layout);
                            }
                        }
                    }
                }
            }
        }
        return $res;
    }


    function load( $pType ){

        $res = array();
        foreach( kryn::$configs as $config ){
            if( $config['themes'] ){
                foreach( $config['themes'] as $themeTitle => $theme ){
                    if( $theme[$pType] ){
                        $res[ $themeTitle ] = $theme[$pType];
                    }
                }
            }
        }
        return $res;
    }
    
    function get( $pFile, $pPlain = false ){

        $rsn = getArgv('rsn')+0;
        $page = dbTableFetch('system_pages', 1, "rsn = $rsn");
        kryn::$current_page = $page;
        kryn::$page = $page;
        tAssign('page', $page);
        kryn::$domain = dbTableFetch('system_domains', 1, "rsn = ".$page['domain_rsn']);
        kryn::loadMenus();

        $pFile = str_replace("..","",$pFile);
        if( $pFile != '' )
            $res['tpl'] = tFetch( $pFile );
            
        if( $res['tpl'] ){
            $res['tpl'] = preg_replace('/\{krynContent ([^\}]*)\}/', '<div>{krynContent $1}</div>', $res['tpl']);
            $res['tpl'] = preg_replace('/\{slot ([^\}]*)\}/', '<div>{slot $1}</div>', $res['tpl']);
        	
        }
            
        $css = array();
        foreach( kryn::$cssFiles as &$file ){
            if( $mtime = @filemtime("inc/template/$file") )
                $css[] = $file . '?modified='.$mtime;
        }
        $res['css'] = $css;
        json( $res );
    }

}

?>
