<?php

namespace MediaSearchMeasureHits\Jobs;

/**
 * Record the media search totalhits for a random sample of articles in a list.
 */
class RecordRandomSample {

    private $config;

    public function __construct( $config ) {
        $this->config = $config;
    }

    public function run() {
        $start_time = microtime(true);
        echo "Running...\n";

        $filename = $this->config['filename'];
        $langCode = $this->config['langCode'];
        $limit = $this->config['limit'] || 1000;
        $resultsFilename = 'output/results_' . $langCode . '_' . time() . '.tsv';
        $resultsFile = fopen( $resultsFilename, 'w+' );

        $file = file_get_contents( $filename );

        // Get array of rows, remove the first one which is column titles.
        $rows = explode( "\n", $file);
        array_shift( $rows );

        $count = 0;
        $indecesToSearch = $this->getUniqueRandomNumbers( 0, count($rows) / 2, $limit );
        foreach ( $indecesToSearch as $index ) {
            $row = $rows[$index];
            $rowData = explode( "\t", $row);
            if ( isset( $rowData[1] ) ) {
                // Record result.
                $totalhits = $this->getMediaSearchTotalHits( $rowData[1], $langCode );
                $rowData[] = $totalhits;
                // Doing it this way vs. fputcsv saves us from having extra
                // quotation marks around the title...
                fputs( $resultsFile, implode( "\t", $rowData ) . "\n" );

                // We'll also echo a count at the end of articles with results.
                if ( $totalhits > 0 ) {
                    $count++;
                }
            }
        }

        echo "Number of articles with at least one result: " . $count . "\n";

        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time);
        echo "Execution time: " . $execution_time ." sec\n";
    }

    private function getUniqueRandomNumbers($min, $max, $quantity) {
        $numbers = range($min, $max);
        shuffle($numbers);
        return array_slice($numbers, 0, $quantity);
    }

    private function getMediaSearchTotalHits( $searchTerm, $langCode ) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $params = [
            'format' => 'json',
            'uselang' => $langCode,
            'action' => 'query',
            'generator' => 'search',
            'gsrsearch' => "filetype:bitmap " . $searchTerm,
            'gsrnamespace' => 6,
            'gsrlimit' => 10,
            'gsroffset' => 0,
            'gsrinfo' => 'totalhits',
            'mediasearch' => 1
        ];

        $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query( $params );

        curl_setopt( $ch, CURLOPT_URL, $url );
        $result = curl_exec( $ch );
        if ( curl_errno( $ch ) ) {
            echo curl_error( $ch ) . ': ' . curl_errno( $ch ) . "\n";
            die( "Exiting because of curl error\n" );
        }
        curl_close( $ch );
        $response = json_decode( $result, true );
        $totalhits = isset( $response['query'] ) && isset( $response['query']['searchinfo'] ) && isset( $response['query']['searchinfo']['totalhits'] ) ?
            $response['query']['searchinfo']['totalhits'] : 0;

        return $totalhits;
    }
}

$config = getopt( '', [ 'filename:', 'langCode:' ] );

$job = new RecordRandomSample( $config );
$job->run();
echo "Done\n";