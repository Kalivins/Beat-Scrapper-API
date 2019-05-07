<?php
require 'autoloader.php';

use ScoreSaberApi\ProfileHandler;
use ScoreSaberApi\SearchHandler;

$router = new AltoRouter();
$router->setBasePath('BSApi/');

$router->map( 'GET', '/profile/[a:slug]', function( $slug ) {
    
    $handler = new ProfileHandler();
    $profile = $handler->getGeneralProfile($slug);
    $scores = $handler->getRecentScores($profile['id'], 10);
    $topranks = $handler->getTopRanks($profile['id'], 10);
    $array = [];
    $array2 = [];
    foreach($scores as $key => $score) {
        if($key <= 10) {
            $array[$key]['name'] = $score->getSong()->getName();
            $array[$key]['difficulty'] = $score->getSong()->getDifficulty();
            $array[$key]['image'] = $score->getSong()->getImage();
            $array[$key]['mapper']['name'] = $score->getSong()->getMapper();
            $array[$key]['mapper']['url'] = $score->getSong()->getMapperUrl();
            $array[$key]['download'] = $score->getSong()->getDownloadUrl();
            $array[$key]['score'] = $score->getPp() . 'pp ' . $score->getAccuracy();
            $array[$key]['rank'] = $score->getRank();
        }
    }
    foreach($topranks as $key => $score) {
        if($key <= 10) {
            $array2[$key]['name'] = $score->getSong()->getName();
            $array2[$key]['difficulty'] = $score->getSong()->getDifficulty();
            $array2[$key]['image'] = $score->getSong()->getImage();
            $array2[$key]['mapper']['name'] = $score->getSong()->getMapper();
            $array2[$key]['mapper']['url'] = $score->getSong()->getMapperUrl();
            $array2[$key]['download'] = $score->getSong()->getDownloadUrl();
            $array2[$key]['score'] = $score->getPp() . 'pp ' . $score->getAccuracy();
            $array2[$key]['rank'] = $score->getRank();
        }
    }
    $profile['recentScores'] = $array;
    $profile['topRanks'] = $array2;
    
    echo json_encode($profile);

});

$router->map( 'GET', '/songs/[a:action]', function( $action ) {

    $searcher = new SearchHandler();
    switch($action) {

        case 'ranked':
            $array = array();
            $songs = $searcher->getLastRankedSongs();
            foreach($songs as $key => $song) {
                if($key <= 10) {
                    $array[$key]['name'] = $song->getName();
                    $array[$key]['difficulty'] = $song->getDifficulty();
                    $array[$key]['image'] = $song->getImage();
                    $array[$key]['mapper']['name'] = $song->getMapper();
                    $array[$key]['mapper']['url'] = $song->getMapperUrl();
                    $array[$key]['download'] = $song->getDownloadUrl();
                }
            }
            $result['ranked_maps'] = $array;
            echo json_encode($result);
            break;


    }
});

$match = $router->match();

if( $match && is_callable( $match['target'] ) ) {
    call_user_func_array( $match['target'], $match['params'] );
} else {
    header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}

