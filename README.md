A search plugin for Moodle!

About
==========
This allows Moodle users to search for courses and resources.
Searches can be site-wide or in a specific course.
Users can search from the search page (http://your.moodle.url/blocks/search), or you can add the search box as a Block to your course pages.

Screenshot of search page.
![Screenshot of search page](http://img.ctrlv.in/img/52e0c33b094d7.jpg)

A regular search only shows things the user has access to. You can optionally include all results.
![Screenshot of all results](http://img.ctrlv.in/img/52e0c34d9c12a.jpg)

Add the block to a course's page to allow users to search within that course.
![Search block on a course page](http://img.ctrlv.in/img/52e0c30c05b16.jpg)

Add the block to a course's page to allow users to search within that course.
![Results from a single course](http://img.ctrlv.in/img/52e0c329802d0.jpg)

Installation
==========
Simply clone the repo to your moodle/blocks directory
```bash
$ cd /path/to/moodle/blocks
$ git clone https://github.com/antriver/moodle-block_search.git search
```
Then login to your Moodle as admin and it will install.
Visit http://your.moodle.url/blocks/search to perform a search.

Settings
==========
Access the settings for the block at **Site Administration > Plugins > Blocks > Seach**
Here you can choose which tables in the database to look for results in and change caching options (by default search results will be cached for 1 day).

![Admin options 1](http://img.ctrlv.in/img/52e0c3742584a.jpg)

![Admin options 2](http://img.ctrlv.in/img/52e0c38b04a9e.jpg)

Important Note
==========
This is distributed with the hope that it will be helpful to others, but with no warranty or guarantee that it works  whatsoever. This should be treated as beta software and is likely to be buggy. Back up your data before isntalling, and use at your own risk! 

Test on Moodle 2.8.3+ with Postgres and MySQL databases.

Apparently it doesn't work with MS SQL databases.

Credits
==========
Created by [Anthony Kuske](http://www.anthonykuske.com), with help from [Adam Morris](http://mistermorris.com/), at [Suzhou Singapore International School](http://www.ssis-suzhou.net)

[![Donate](https://www.paypalobjects.com/en_GB/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=anthonykuske%40gmail%2ecom&lc=GB&item_name=Anthony%20Kuske&no_note=0&cn=Add%20a%20note%3a&no_shipping=1&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted)
