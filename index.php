<?php
require 'autoloader.php';

use BeatSaberScrapperApi\ProfileHandler;
use BeatSaberScrapperApi\SearchHandler;
use BeatSaberScrapperApi\ImportHandler;

$router = new AltoRouter();
$router->setBasePath('scrapper/');

$router->map( 'GET', '/import', function() {
    $handler = new ImportHandler();
    
    $params['type'] = '0,1,2,3,4';
    $params['userid'] = $handler->getAllImportedUsers();

    $handler->makeImport($params);
});

$router->map( 'GET', '/profile/[a:slug]', function( $slug ) {
    
    $handler = new ProfileHandler();
    $profile = $handler->getGeneralProfile($slug, true);
    if(!empty($profile['error']) || !empty($profile['import'])) {
        header('Content-Type: application/json');
        unset($profile['import']);
        echo json_encode($profile);
        die();
    }
    $scores = $handler->getRecentScores($profile['id'], 10);
    $topranks = $handler->getTopRanks($profile['id'], 10);
    $recentScores = $handler->formatScores($scores);
    $topRanks = $handler->formatScores($topranks);
    $profile['recentScores'] = $recentScores;
    $profile['topRanks'] = $topRanks;
    ImportHandler::importUserFromJson($profile['id'], $profile);

    header('Content-Type: application/json');
    echo json_encode($profile);

});

$router->map( 'GET', '/songs/[a:action]', function( $action ) {

    $searcher = new SearchHandler();
    $actionAvailable = $searcher->getAllTypes();
    if(!in_array($action, $actionAvailable)) {
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'songs route need a valid action parameter ('.implode(', ', $actionAvailable).')'));
        die();
    }
    $songs = $searcher->getLastSongs($action, true);
    if(!empty($songs['import'])) {
        header('Content-Type: application/json');
        unset($songs['import']);
        echo json_encode($songs);
        die();
    }
    $maps = $searcher->formatSongs($songs);
    $result[$action.'_maps'] = $maps;

    header('Content-Type: application/json');
    echo json_encode($result);

});

$router->map( 'POST', '/songs', function() {

    $searcher = new SearchHandler();
    $songs = $searcher->requestSongs($_POST);
    if(!empty($songs['error'])) {
        echo json_encode($songs);
        die();
    }
    $maps = $searcher->formatSongs($songs);
    $result['searched_maps'] = $maps;
    
    header('Content-Type: application/json');
    echo json_encode($result);
});

$match = $router->match();

if( $match && is_callable( $match['target'] ) ) {
    call_user_func_array( $match['target'], $match['params'] );
} else {
    header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}

