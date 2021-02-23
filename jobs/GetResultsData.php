<?php

namespace MediaSearchMeasureHits\Jobs;

/**
 * Get number of articles with at least one media search result.
 */
class GetResultsData {

    private $config;

    public function __construct( $config ) {
        $this->config = $config;
    }

    public function run() {
        $filename = $this->config['filename'];
        $file = file_get_contents( $filename );

        $rows = explode( "\n", $file);

        $count = 0;
        foreach ( $rows as $index => $row ) {
            // Remove final empty row so we can accurately calculate data below.
            if ( $row === '' ) {
                unset( $rows[$index] );
            }

            $rowData = explode( "\t", $row);
            if ( isset( $rowData[2] ) && $rowData[2] > 0 ) {
                $count++;
            }
        }

        echo "Number of articles with at least one result: " . $count . "\n";
        echo "Number of articles tested: " . count( $rows ) . "\n";
        echo "Percentage of articles with results: " . $count * 100 / count( $rows ) . "%\n";
    }
}

$config = getopt( '', [ 'filename:' ] );

$job = new GetResultsData( $config );
$job->run();
echo "Done\n";