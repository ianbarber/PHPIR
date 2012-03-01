<?php

$screenname = "ianbarber";
$matrix_file = $screenname . "tmatrix.php";

// First get a list of my followers
$following = getFromCache( $screenname );

if( !file_exists( $matrix_file ) ) {
    // Now build a matrix of followers
    $userlookup = array_flip( $following->ids );
    $matrix = array();
    $followercount = count( $following->ids );
    foreach( $following->ids as $key => $id ) {
        $user = array_fill( 0, $followercount, 0 );
        $their_following = getFromCache( $id );    
        $intersect = array_intersect( $following->ids, $their_following->ids );
    
        if( count( $intersect ) ) {
            $divisor = 1 / count( $intersect );
            foreach( $intersect as $shared_id ) {
                $user[$userlookup[$shared_id]] = $divisor;
            }
        }
    
        $matrix[$key] = $user;
    }
    file_put_contents( $matrix_file, '<?php $tmatrix = ' . var_export( $matrix, true ) . ";");
    echo "Run again to calculate users\n";
} else {
    include $matrix_file;
    $vector = array();
    $rc = Lapack::eigenValues( $tmatrix, $vector );
    $result = array();
    foreach( $vector[0] as $key => $value ) {
        $result[$following->ids[$key]] = $value[0];
    }
    arsort( $result );

    $url = "https://api.twitter.com/1/users/lookup.json?user_id=" . 
        implode(",", array_slice( array_keys( $result ), 0, 10 ) );
    $contents = json_decode( file_get_contents( $url ) );
    foreach( $contents as $i => $user ) {
        echo $i, ": ", $user->screen_name, " ", "\n";
        
    }
}

function getFromCache( $id ) {
    $cacheFile = 'cache/' . $id . ".json";
    if( !file_exists( $cacheFile ) ) {
        $access = is_numeric( $id ) ? 'user_id=' . $id : "screen_name=" . $id;
        
        // Using cURL so we can get the error code
        $ch = curl_init();
        curl_setopt ( $ch, CURLOPT_URL, "http://api.twitter.com/1/friends/ids.json?" . $access );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $file = curl_exec( $ch ); 
        
        if( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) == 401 ) {
            // account for private users
            $data = new stdClass();
            $data->ids = array();
            $file = json_encode($data);
        } else if( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) == 200 ){
            $data = json_decode($file);
        } else {
            die( "Twitter rate limit hit, try again later");
        }
        
        curl_close( $ch );
        file_put_contents( $cacheFile, $file );
    } else {
        $data = json_decode( file_get_contents( $cacheFile ) );
    }
    return $data;
}
