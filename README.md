Pro Broken Links Maintainer
---------------------
Do you want easily find and fix your broken links ?
You should try this great modern plugin !
Easily find broken links in background, speed up using third party APIs like youtube.
Browse broken urls, edit them or delete.
Get email notification after job is done !
Customise process as you wish.
---------------------

After activating plugin it will start parsing urls in your posts.
Parsed urls can be browsed by using `Urls Listing` position in menu.
If you wish to delete and parse them again, you can use `delete and parse again all urls button`.

When urls are parsed first go into Settings menu.
Under `Keys` tab please set API keys, to get them click link over input fields.

Scan on Load tab is used for auto checking post urls, while visiting them.
	- Check post urls on load - to turn it on/off.
	- Use Google API - is needed if you wish to check channel/user/playlist
	- Last scan date difference in hours - if set for example to 24 then post will be checked one time per day maximum

After first setup go to `Scan BG` position.
Under `Basic` tab you have following options:

	- Hours between last scan - check urls one time per X hours window, ie: 72 will check urls if then were not checked in last 72 hours
	- Send email after finished background scan. - send email notification after job is done ( can be send using Mandrill API to avoid emails landing in spam )
	- Use Google Api - to speed up checking youtube urls
	- Filters - ie: you can set: Filter urls: youtube, openvideo and Filter out urls: playlist, this will check only youtube + openvideo urls, but without youtube playlists

Under `Advanced` tab
	- Urls to query in each loop ( if more than heavier queries ) - it affects on quering database, if less then plugin will make queries often, but if more then less but they will retrieve more data from database and can be heavier.

During scan, you can abort it using `Stop scanning` button, process will be aborted after current urls package will be checked.
For example if in Advanced tab is set 100 for: `Urls to query in each loop` and stop will be pushed after 10 urls checked, then after checking next 90 will stop.

What is in premium version ?
	- Scan background - use google api, filter urls, option specifying whether post urls should be scanned from the last scan after a specified time
	- Invalid Urls - edit button
	- Use mailgun to send notification emails
	- Use google api in on post load scan
