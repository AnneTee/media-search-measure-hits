<?php

namespace MediaSearchMeasureHits\Jobs;

/**
 * Record the media search totalhits for each article in a list.
 */
class RemoveDisambiguationPages {

    private $config;

    public function __construct( $config ) {
        $this->config = $config;
    }

    public function run() {
        $start_time = microtime(true);
        echo "Running...\n";

        $filename = $this->config['filename'];
        $resultsFilename = 'input/' . $this->config['langCode'] . 'wiki_articles_trimmed_' . time() . '.tsv';
        $resultsFile = fopen( $resultsFilename, 'w+' );

        $file = file_get_contents( $filename );

        // Get array of rows, remove the first one which is column titles.
        $rows = explode( "\n", $file);
        array_shift( $rows );

        // For each batch of 50 articles...
		$offset = 0;
		$limit = 50;
		$count = 0;
		while ( $offset < count( $rows ) ) {
			$pageIds = [];

			// Build array of pageids.
			for ( $i = $offset; $i < $offset + $limit; $i++ ) {
				if ( isset( $rows[$i] ) ) {
					$rowData = explode( "\t", $rows[$i]);
					if ( isset( $rowData[2] ) ) {
						$pageIds[] = $rowData[2];
					}
				}
			}

			// Get pages and, if they're not a disambiguation page, write data
			// for that page to the new file.
			$pages = $this->getPages( $pageIds );
			foreach ( $pages as $page ) {
				if ( !$this->isDisambiguationPage( $page ) && isset( $page['title'] ) ) {
					$pageData = [	
						$page['pageid'],
						$page['title']
					];
					// Doing it this way vs. fputcsv saves us from having extra
					// quotation marks around the title...
					fputs( $resultsFile, implode( "\t", $pageData ) . "\n" );
				} else {
					$count++;
				}
			}

			echo $offset . " titles tested...\n";

			$offset += $limit;
		}

		echo "Number of disambiguation pages removed: " . $count . "\n";

        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time);
        echo "Execution time: " . $execution_time ." sec\n";
    }

	private function getPages( $pageIds ) {
		$langCode = $this->config['langCode'];
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $params = [
            'format' => 'json',
			'redirects' => true,
            'uselang' => $langCode,
            'action' => 'query',
			'pageids' => implode( '|', $pageIds ),
            'prop' => 'pageprops',
            'ppprop' => 'disambiguation',
        ];

        $url = 'https://' . $langCode . '.wikipedia.org/w/api.php?' . http_build_query( $params );

        curl_setopt( $ch, CURLOPT_URL, $url );
        $result = curl_exec( $ch );
        if ( curl_errno( $ch ) ) {
            echo curl_error( $ch ) . ': ' . curl_errno( $ch ) . "\n";
            die( "Exiting because of curl error\n" );
        }
        curl_close( $ch );
		$response = json_decode( $result, true );
		return $response['query']['pages'];
    }

    private function isDisambiguationPage( $page ) {
        return isset( $page['pageprops'] ) && array_key_exists( 'disambiguation', $page['pageprops'] );
    }
}

$config = getopt( '', [ 'filename:', 'langCode:' ] );

$job = new RemoveDisambiguationPages( $config );
$job->run();
echo "Done\n";
