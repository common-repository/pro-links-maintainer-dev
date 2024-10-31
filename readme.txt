=== Pro Broken Links Maintainer ===
Contributors: maciejbak85, freemius
Tags: broken links, broken, links, maintenance, seo
License: GPL v3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 4.9
Tested up to: 5.3
Requires PHP: 5.6.40

Easily find and fix broken links on your site.

== Description ==

After activating plugin, wait a while until it will parse urls from all your posts.
Click Scan BG, to find broken urls.
Scan Youtube urls faster by using google API's.
Go to Invalid Urls page and fix broken ones.
Use scan on load to check once a day top post's urls.

Enjoy improved seo and user experience.

[youtube https://youtu.be/8J4rDp2kYKQ]

== Installation ==
You may install the plugin using following methods:

1. Unzip file and using an ftp program upload it to the wp-content/plugins/ directory then activate in plugins page.
2. Using the search field in the admin plugins area type in "network posts extended" (without quotes) then install from there.
3. Upload zip file through the standard plugins menu.


Remember to enable Wordpress API ( Settings -> Permalinks -> Post Name ), otherwise plugin won't work !
You can check video how to install, enable API and run simple scan:
[youtube https://youtu.be/8J4rDp2kYKQ]

== Frequently Asked Questions ==
Q) How does plugin takes urls from posts ?
A) After activating plugin it will start parsing urls in your posts, also after saving new post it does the same job but only for this new post.
[youtube https://youtu.be/-T1jhkIX1Mc]

Q) Where can I check parsed urls ?
A) Parsed urls can be browsed by using `Urls Listing` position in menu. If you wish to delete and parse them again, you can use `delete and parse again all urls button`.

Q) What for is scan on load in settings ?
A) Scan on Load tab is used for auto checking post urls, while visiting them.
	- Check post urls on load - to turn it on/off.
	- Use Google API - is needed if you wish to check channel/user/playlist
	- Last scan date difference in hours - if set for example to 24 then post will be checked one time per day maximum
[youtube https://youtu.be/-qbz4LbNHJ8]	

Q) How to scan broken links ?
A) Go to `Scan BG` menu position and press scan.
[youtube https://youtu.be/kgtJYWvRpVU]

Q) Where to check scan results ?
A) In `Invalid Urls page`
[youtube https://youtu.be/e6GzpYIcSYw]

Q) How `Filter out urls` works ?
A) It will omit urls containing word, for example if in database there are only youtube and openload links, by adding openload into this textarea plugin will check only youtube videos

Q) Can I abort scan ?
A) During scan, you can abort it using `Stop scanning` button, process will be aborted after current urls package will be checked.
For example if in Advanced tab is set 100 for: `Urls to query in each loop` and stop will be pushed after 10 urls checked, then after checking next 90 will stop.

Q) How `Filter out urls` works ?
A) It will omit urls containing word, for example if in database there are only youtube and openload links, by adding openload into this textarea plugin will check only youtube videos

Q) How `Urls to query in each loop` works ?
A) By setting this number to for example 100:
Algorithm will take 100 urls from database ( 1 query - if this number is higher than query take more time of course as more data will be in response ).
Counter of checked links is updated only after one loop is finished ( optimization ), so user will see updates in packages like 100/5000, 200/5000, 300/5000 etc.
Also if user will try to stop background scan if let's say 5 of 100 links were checked then will have to wait for those 95 not checked to abort process it is also optimization.

Q) What is in premium version ?
A) - Scan background - use google api, filter urls, option specifying whether post urls should be scanned from the last scan after a specified time
	 - Invalid Urls - edit button
	 - Use mailgun to send notification emails
	 - Use google api in on post load scan

Q) What should I do first after activating premium plugin ?
A) First go into Settings menu, under `Keys` tab please set API keys, to get them click link over input fields.

Q) For what is premium feature `Hours between last scan` ?
A) check urls one time per X hours window, ie: 72 will check urls if then were not checked in last 72 hours,
it will omit posts urls which were scanned on load for example in last 72 hours.

Q) How works send email after finished background scan ?
A) In premium version it will send email using mailgun, in free simple SMTP ( so can land in spam folder )

Q) For what do I need using google API ( premium feature )?
A) To speed up checking youtube urls, it will group youtube links, and send them to check in one request, so 100 youtube urls can be checked by doing one request instead of doing 100

Q) How `Filter urls containing text` ( premium feature ) ?
A) By adding ie: youtube, dailymotion there plugin will check only links from youtube and dailymotion and omit others

== Screenshots ==
1. Parsed urls listing
2. Scan background basic settings tab
3. Scan background advanced settings tab
4. Scan background email
5. Invalid urls listing
6. Third party APIs Settings
7. Scan on load settings
8. Menu

== Changelog ==
= 1.1.7.5 =
Fixed bug with duplicated Error, in error code
= 1.1.7.4 =
name change, bug fixes
= 1.1.7 =
Fixed bugs in url checker.
= 1.1.6 =
Fixed bugs, improved code for PHP 5.6.40 compatibility
= 1.0.5 =
Settings option bug fix, updated tested up wordpress and php versions
= 1.0.4 =
Name update
= 1.0.3 =
Updated readme file with FAQ, and changed short and long descriptions.
