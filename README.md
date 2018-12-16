Google Analytics Counter 8.x-1.0-alpha1
---------------------------------------

Table of Contents
-----------------

* Introduction
* Goals
* Installation
* Create a Project in Google.
* The custom Google Analytics Counter field.
* Project status

### Introduction

Google Analytics Counter is a scalable, lightweight page view counter drawing
on data collected by Google Analytics.

The primary uses for this module is to:

- Add a custom Google Analytics Counter field which contains the count of page views from Google Analytics API.
  - Once cron has populated the custom field, the custom field can be used like any other node field. This makes the custom field good for Drupal views or inclusion on Drupal page displays.
  - Views can also be based on the Google Analytics Counter tables directly, but using the custom field makes much more sense.
- Add a customizable block which contains the count of page views from Google Analytics API.
- Add a filter to print the page views within text fields.

- A secondary function for this module is to:
Use the data Drupal is collecting from Google Analytics API for other things, like search.

### Goals

- A comprehensive and understandable solution to storing Google Analytics Pageviews in Drupal.
- A custom field in Drupal which displays Google Analytics Pageviews.
  - A custom field can be used like any other field. This makes the custom field good for Drupal views or inclusion on Drupal page displays.
- A themable block in Drupal which displays Google Analytics Pageviews.
- A filter which adds the pageviews inline.
- Create views based on the Google Analytics Counter..
  - This feature may be deprecated in favor of the custom field.

### Installation

1. Copy/upload the google analytics counter module to the modules directory of
   your Drupal installation.

2. Enable the 'Google Analytics Counter' module in 'Extend' (/admin/modules).

3. Set up user permissions (/admin/people/permissions#module-google_analytics_counter).

4. Go to the settings page (/admin/config/system/google-analytics-counter).
   Review default settings and adjust accordingly. For instance, the "Minimum time 
   to wait before fetching Google Analytics data (in minutes)" field can usually 
   be set to 0 except on the largest systems. Likewise, on larger systems, 
   it may be preferable to increase the "Queue Time (in seconds)" in order to 
   process all the queue items in a single cron run.
   
5. Click Save configuration.

6. Go to the authentication page (/admin/config/system/google-analytics-counter/authentication).

7. Add your Google Project Client ID, Client Secret and Authorized Redirect URI. 
   See "Creating a Project in Google" in the next section of this document 
   for more information on setting up a Google Analytics project.

8. Optionally add your Google Project's machine name. This field helps to take 
   you directly to your Analytics API page to view Google's quotas.

9. Click Save configuration.

10. Click the large button at the top of the page labeled 'Authenticate'.

11. In the pop up that appears from Google, select the google account to which 
   you would like to authenticate.

12. Fill in credentials if requested by Google.
    Click Allow.

13. If all goes well, you will be returned to the authentication page in Drupal 
    because that is the URL you added to the "Authorized Redirect URI" field when
    setting up configuration
    (/admin/config/system/google-analytics-counter/authentication).
    
14. Select a view from the select list in Google View. A way to tell that 
    authentication with google has succeeded is if you see your Google Analytics
    profiles listed in Google Views. If you did not successfully authenticate
    with Google the only option in Google View is 'Unauthenticated'.
    
15. Click Save configuration.

16. Go to the Custom field page (/admin/config/system/google-analytics-counter-configure-types).

17. Check the checkbox for any content types to which you would like to add the
    custom Google Analytics Counter field. You can also remove the custom field
    quickly by checking the Remove the custom field checkbox at the top of the page.

18. Click Save configuration.

19. Note that you will need to manually add the custom Google Analytics Counter
    field to the form display (/admin/structure/types/manage/doctor/form-display) and display
    (/admin/structure/types/manage/doctor/display) for any content types that to
    which you've added the custom field. The data is stored and ready to use. You
    just have to enable the field per your requirements.
 
20. Check the Dashboard (/admin/config/system/google-analytics-counter/dashboard)

21. Note: most of the numbers are 0 until you run cron.

22. Run cron. Generally speaking, it is a good idea to run cron continuously
    with a scheduler like Jenkins or a crontab to keep pageviews data up to date.

23. Use the custom Google Analytics Counter field in Drupal Views or on the page
    display as you see fit.

24. Place a Google Analytics Counter block on your site.
    (/admin/structure/block).

25. Enable the text filter (More to come).

### Create a Project in Google.

1. Go to https://console.developers.google.com/cloud-resource-manager
   Click Create project.

2. Name your project.
   Click Create. Wait several moments for your project to be created.

3. Go to https://console.developers.google.com/apis/dashboard
   You will most likely be directed to your project, or select your project by
   selecting your project's name in the upper left corner of the browser next to
   where it says Google APIS.

4. Click Enable APIS and services on the Google APIs dashboard.
   Search for Analytics API.
   Click Analytics API.
   On the proceeding page, click Enable.

5. You will be sent back to the Google APIs page. Click Credentials in the 
   left-hand column.

6. Click Create credentials. Select OAUTH client ID.

7. Click Configure consent screen.
   Fill out the OAuth consent screen form.
   Click Save.

8. You are sent back to the page where you can select your Application type.
   Select Web application.

9. Name it in the Name field.

10. Leave the Authorized JavaScript origins field blank.

11. Add a url to the Authorized redirect URIs.
    Example: http://localhost/d8/admin/config/system/google-analytics-counter/authentication
    Click Create.
    Click Create again.

12. Note your client ID and client secret.
    You may also get your client ID and client secret by clicking the pencil icon
    on the right side of the Credentials page next to your application name.

13. Copy your client ID client secret, and Authorized redirect URIs from Google
     and add them to analytics authentication form in the module.
     (/admin/config/system/google-analytics-counter/authentication).

### The custom Google Analytics Counter field.

A custom Google Analytics Counter field has been added to version 8.x-3.0-alpha4
and above. Having the Google pageviews in a custom fields makes adding Google
pageviews to form display, page display, or Drupal views the same as adding 
any other field.

After installing version 8.x-3.0-alpha4 or above or running the module updates
for existing installations with `drush updatedb` The field storage for the 
Google Analytics Counter field is added to the system.

To add the custom field to a content type or multiple content types, 
go to the Custom field tab (/admin/config/system/google-analytics-counter-configure-types)
and check the content types you would like to add the custom field to.

The custom field can also be removed from the system without concern. To remove
the custom field, go to (/admin/config/system/google-analytics-counter-configure-types)
and check the "Remove the custom field" checkbox. 

Click Save configuration.

To add the custom field to a content type again, go back to the custom field tab,
uncheck the "Remove the custom field" field, and check those content types you
would like to add the field to.

### Project Status

- [Port google analytics counter module to drupal 8](https://www.drupal.org/project/google_analytics_counter/issues/2695915)
Author: Tomas Fulopp (Vacilando) for Drupal 7, Eric Sod (esod) for Drupal 8.

