# Moodle Search Plugin
Add a search function to your Moodle site. This allows Moodle users to search for courses and resources within them. Searches can be site-wide or in a specific course. Users can search from the search page (http://your.moodle.url/blocks/search), or you can add the search box as a block on your course pages.

## Screenshots
![Screenshot of search page](https://www.classroomtechtools.com/assets/img/moodle-plugin-screenshots/block_search/1.png)
![Screenshot of results](https://www.classroomtechtools.com/assets/img/moodle-plugin-screenshots/block_search/2.png)
![Screenshot of results](https://www.classroomtechtools.com/assets/img/moodle-plugin-screenshots/block_search/3.png)

## Setup
Simply clone the repo to your moodle/blocks directory
```bash
$ cd /path/to/moodle/blocks
$ git clone https://github.com/antriver/moodle-block_search.git search
```
Then login to your Moodle as admin and it will install.
Visit http://your.moodle.url/blocks/search to perform a search.

## Settings
Access the settings for the block at **Site Administration > Plugins > Blocks > Seach**
Here you can choose which tables in the database to look for results in and change caching options (by default search results will be cached for 1 day).

![Admin settings](https://www.classroomtechtools.com/assets/img/moodle-plugin-screenshots/block_search/4.png)
![Admin settings](https://www.classroomtechtools.com/assets/img/moodle-plugin-screenshots/block_search/5.png)

## Important Note
This is distributed with the hope that it will be helpful to others, but with no warranty or guarantee that it works  whatsoever. This should be treated as beta software and is likely to be buggy. Back up your data before isntalling, and use at your own risk! 

Test on Moodle 2.8.3+ with Postgres and MySQL databases.

Apparently it doesn't work with MS SQL databases.

## Credits
Created by [Anthony Kuske](http://www.anthonykuske.com), with help from [Adam Morris](http://mistermorris.com/), at [Suzhou Singapore International School](http://www.ssis-suzhou.net)

[![Donate](https://www.paypalobjects.com/en_GB/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=anthonykuske%40gmail%2ecom&lc=GB&item_name=Anthony%20Kuske&no_note=0&cn=Add%20a%20note%3a&no_shipping=1&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted)
