<?php
require 'autoloader.php';

use ScoreSaberApi\ProfileHandler;
use ScoreSaberApi\SearchHandler;

$router = new AltoRouter();
$router->setBasePath('scrapper/');

$router->map( 'GET', '/profile/[a:slug]', function( $slug ) {
    
    $handler = new ProfileHandler();
    $profile = $handler->getGeneralProfile($slug);
    if(!empty($profile['error'])) {
        echo json_encode($profile);
        die();
    }
    $scores = $handler->getRecentScores($profile['id'], 10);
    $topranks = $handler->getTopRanks($profile['id'], 10);
    $recentScores = $handler->formatScores($scores);
    $topRanks = $handler->formatScores($topranks);
    $profile['recentScores'] = $recentScores;
    $profile['topRanks'] = $topRanks;
    
    echo json_encode($profile);

});

$router->map( 'GET', '/songs/[a:action]', function( $action ) {

    $searcher = new SearchHandler();
    $actionAvailable = $searcher->getAllTypes();
    if(!in_array($action, $actionAvailable)) {
        echo json_encode(array('error' => 'songs route need a valid action parameter ('.implode(', ', $actionAvailable).')'));
        die();
    }
    $songs = $searcher->getLastSongs($action);
    $maps = $searcher->formatSongs($songs);
    $result[$action.'_maps'] = $maps;
    echo json_encode($result);

});

$router->map( 'POST', '/songs', function() {

    $searcher = new SearchHandler();
    $params = $_POST;
    print_r($params);
});

$match = $router->match();

if( $match && is_callable( $match['target'] ) ) {
    call_user_func_array( $match['target'], $match['params'] );
} else {
    header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}

