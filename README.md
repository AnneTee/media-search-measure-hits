# media-search-record-hits

This tool can be used to run a list of titles through media search on Wikimedia
Commons and record some simple data, including:
* Total number of hits per title
* Number of titles that have at least one result
* Percentage of titles with results

## Usage

To record the number of media search hits per title in a list of titles:

```
php jobs/RecordResultsCount.php --filename="input/[filename]" --langCode="[langcode]"
```

To get results data:

```
php jobs/GetResultsData.php --filename="output/[filename]"
```
