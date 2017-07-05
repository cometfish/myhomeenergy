This is a short script to pull data from Ausnet Services' MyHomeEnergy website.

You can sign up to the MyHomeEnergy website if you are in the Ausnet Services electricity distribution zone, and you have a smart meter. You can see meter data for your electricity import, as well as solar export, and you can even enter your tariff info into the myhomeenergy website settings, which will then calculate the cost of your electricity based on time of use. (Note: the website data is not instant like an in-home display would be – it is delayed by 6 hours, but hopefully in the future this data will be more up to date.)

The script pulls down the current day's, and the previous day's data (kWh and dollar cost) and displays it on a web page (for quick checks on your mobile).
You could extend this by permanently displaying it on a small tablet or RaspberryPi/Arduino setup, to create your own in-home display!

I've also included a script to upload your previous day's import and export data to PVOutput! Set it up as a cron job – remember that the myhomeenergy data is delayed, so I've set mine up to run at midday.
(Unfortunately meters don't measure Consumption so you'll have to get that figure directly from your inverter. If you're already exporting Consumption to PVOutput, delete the line starting with ‘g' => ... so that your consumption value isn't overwritten.)

today.php
---------
Open today.php in a text editor, and change the top login details to your own myhomeenergy login details. Upload to a web server, and access it to view today's usage data!

upload_yesterday.php
--------------------
Open upload_yesterday.php in a text editor, and change the top login details to your own myhomeenergy login details.
Also change the PVOutput API key and System ID to your own.
Upload to a web server (the path doesn't need to be accessible by web browser if you are creating a cron job), set up a cronjob for midday everyday.

date.php
--------
Occasionally the meter data will take a few days to come up, so your 'upload_yesterday.php' won't have any data to upload.
Upload this file to a web server, then access via /date.php?date=20140130 (date format yyyymmdd) to request a specific day's data

Created 2014-10-08