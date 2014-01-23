A search plugin for Moodle!

About
==========
Allows Moodle users to search for Courses and resources.
Searches can be site-wide or in a specific course.
Users can search from the search page (http://yourmoodle.com/blocks/search), or you can add the search box as a Block to your course pages.

Installation
==========
Simply clone the repo to your moodle/blocks directory
```bash
$ cd /path/to/moodle/blocks
$ git clone https://github.com/antriver/moodle-block_search.git search
```
Then login to your Moodle as admin and it will install.

Settings
==========
Access the settings for the block at **Site Administration > Plugins > Blocks > Seach**
Here you can choose which tables in the database to look for results in and change caching options (by default search results will be cached for 1 day).

Room for Improvement (for developers)
==========
Possible extra features:
* Ability to pick not only the tables to search but which fields too (instead of the defaults).
* Option to disable the "Include results I don't have access to" checkbox.
* Performance should be okay, but the biggest drain is checking what resources the user has access to. `MoodleSearch/DataManager.php:104` loads the 'modinfo' for the course the activity in. This ends up loading the info for all the resources in that course. But as far as I can see Moodle doesn't provide (support) a way to create a 'cm_info' bject without first loading all the course modinfo.
