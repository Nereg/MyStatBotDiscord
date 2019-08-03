<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia\Utils;

/**
 * File orientated helpers.
 */
class FileHelpers {
    /**
     * Performs a recursive file search in the specified path, using the specified search mask.
     * @param string        $path
     * @param string|array  $searchmask
     * @return string[]
     */
    static function recursiveFileSearch(string $path, $searchmask = '*') {
        $path = \rtrim($path, '/');
        
        $files = array();
        if(is_array($searchmask)) {
            $csearchmask = \count($searchmask);
            
            for($i = 0; $i < $csearchmask; $i++) {
                $files = \array_merge($files, \glob($path.'/'.$searchmask[$i]));
            }
            
            \sort($files);
        } else {
            $files = \glob($path.'/'.$searchmask);
        }
        
        
        $dirs = \glob($path.'/*', GLOB_ONLYDIR);
        foreach($dirs as $dir) {
            if(\is_dir($dir)) {
                $files = \array_merge($files, self::recursiveFileSearch($dir, $searchmask));
            }
        }
        
        \sort($files);
        return $files;
    }
}
