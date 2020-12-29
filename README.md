This is a short script to pull data from Ausnet Services' MyHomeEnergy website, and upload it to PVOutput.

You can sign up to the MyHomeEnergy website if you are in the Ausnet Services electricity distribution zone, and you have a smart meter. You can see meter data for your electricity import, as well as solar export, and you can even enter your tariff info into the myhomeenergy website settings, which will then calculate the cost of your electricity based on time of use. (Note: the website data is not instant like an in-home display would be – it is delayed by 6 hours, but hopefully in the future this data will be more up to date.)

The script pulls down the selected day's data (kWh) and displays it on a web page and uploads it to PVOutput.
(Unfortunately meters don't measure Consumption so you'll have to get that figure directly from your inverter. If you're already exporting Consumption to PVOutput, delete the line starting with ‘g' => ... so that your consumption value isn't overwritten.)

## Files

date.php
---------
Open date.php in a text editor, and change the top login details to your own MyHomeEnergy login details (your NMI number).
Open aspxauth.txt in a text editor, and paste in your .ASPXAUTH cookie data (see below).
Also change the PVOutput API key and System ID to your own.
Upload this file to a web server, then access via /date.php?date=20140130 (date format yyyymmdd) to request a specific day's data.
To upload multiple day's data, use /date.php?autoupload=65&date=20140130 (autoupload value is the seconds between each request - PVOutput has API rate limits, so you will want to set this conservatively).

aspxauth.txt
-------------
The new MyHomeEnergy website requires a CAPTCHA challenge ("I'm not a robot" prompt) on login, so it can't be logged into via an automatic script. To work around this, you can login on your browser, then copy the cookie from your session to the script, allowing the script to use your validated session.
Follow these instructions to get your MyHomeEnergy ASPXAUTH cookie data:
* Go to the Ausnet Services' MyHomeEnergy website
* Press F12 to bring up the Developer Tools in your browser
* Login to your MyHomeEnergy site
* View the cookies sent by the page, eg.
  * in Firefox, click the Storage tab on your Developer Tools window, then click https://www.ausnetservices.com.au under Cookies.
* Find the cookie called ".ASPXAUTH" and copy its value (it will be a long alphanumeric value, which looks something like: "E595CAE5...C7A06D06")
Paste the cookie value into the aspxauth.txt file, and upload it alongside the date.php file.
The session will expire, so you will only be able to use date.php while the cookie is still valid. If using the auto-upload function, the cookie value will be refreshed with each upload, so the session should not expire until the upload has finished.

## Changelog

### 2014-10-08
Created script

### 2020-12-29
Updated due to My Home Energy website update (April 2018) which added a CAPTCHA challenge on login.