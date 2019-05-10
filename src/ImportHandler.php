<?php

namespace BeatSaberScrapperApi;

use BeatSaberScrapperApi\ProfileHandler;
use BeatSaberScrapperApi\SearchHandler;

class ImportHandler extends BaseHandler {

    public function makeImport($params) {
        
        $types = !empty($params['type']) ? explode(',', $params['type']) : array();
        $errors = array();
        foreach($types as $type) {
            $errors[] = $this->importLastSongs((int)$type);
        }

        $userids = !empty($params['userid']) ? explode(',', $params['userid']) : array();
        foreach($userids as $id) {
            $errors[] = $this->importUser((int)$id);
        }

        echo json_encode(array('Import Terminée avec '.count($errors)));
        die();

    }

    public static function importUser($userid) {
        $handler = new ProfileHandler();
        $profile = $handler->getGeneralProfile($userid);
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
        $fp = fopen('imports/users/'.$profile['id'].'.json', 'w');
        $status = fwrite($fp, json_encode($profile));
        fclose($fp);
        if($status !== false) {
            echo json_encode(array('importUser' => 'success for '.$profile['id'].'.json'));
        } else {
            echo json_encode(array('importUser' => 'failed to write to '.$profile['id'].'.json'));
            return $profile['id'];
        }
        return;
    }

    public static function importUserFromJson($id, $json) {
        $fp = fopen('imports/users/'.$id.'.json', 'w');
        $status = fwrite($fp, json_encode($json));
        fclose($fp);
        return;
    }

    public function getAllImportedUsers() {
        $userid = array();
        if ($handle = opendir('imports/users/')) {

            while (false !== ($entry = readdir($handle))) {
        
                if (!empty($entry) && $entry != "." && $entry != "..") {
        
                    $userid[] = reset(explode('.', $entry));

                }
            }
        
            closedir($handle);
        }
        return implode(',', $userid);
    }

    public function importLastSongs($type) {
        $searcher = new SearchHandler();
        $songs = $searcher->getLastSongs($type);
        $maps = $searcher->formatSongs($songs);
        $result = $maps;
        $status = $this->addJsonFile($this->getType($type), $result);
        if($status !== false) {
            echo json_encode(array('importSongs' => 'success for '.$this->getType($type).'.json'));
        } else {
            echo json_encode(array('importSongs' => 'failed to write to '.$this->getType($type).'.json'));
            return $type;
        }
        return;
    }

    public static function addJsonFile($fileName, $json) {
        $fp = fopen('imports/'.$fileName.'.json', 'w');
        $status = fwrite($fp, json_encode($json));
        fclose($fp);
        return $status;
    }

}