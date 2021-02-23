# media-search-record-hits

This tool can be used to run a list of titles through media search on Wikimedia
Commons and record some simple data, including:
* Total number of hits per title
* Number of titles that have at least one result
* Percentage of titles with results

## Usage

### Main script

To record the number of media search hits per title in a list of titles:

```
php jobs/RecordResultsCount.php --filename="input/[filename]" --langCode="[langcode]"
```

This script assumes a .tsv file with 2 columns: pageid and title.

### Remove Wikipedia disambiguation pages

Another job, RemoveDisambiguationPages, was designed to strip out Wikipedia
disambiguation pages from the original data since they aren't good candidates
for adding images via the future API and would therefore skew the results of
this test. To run this job:

```
php jobs/RemoveDisambiguationPages.php --filename="input/[filename]" --langCode="[langcode]"
```

### Random sample

You can also take a random sample rather than going through an entire file. This
script will pick n random, unique indeces and fetch totalhits for them, where n
is the limit set via an option (or omit it to use a default limit of 1000).

```
php jobs/RecordRandomSample.php --filename="input/[filename]" --langCode="[langcode]" --limit=10000
```

### Get a summary of results

To get results data:

```
php jobs/GetResultsData.php --filename="output/[filename]"
```
