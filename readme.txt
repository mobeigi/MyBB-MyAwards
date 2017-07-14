License is attached inside extras folder by using this software you agree to the terms of that license.  

Mod Name: My Awards
Mod Author: Jesse Labrocca
Mod Website:  http://www.MybbCentral.com
Mod Version: 2.4
Mod Mybb Compatibility: 1.6x
Mod File Edits: None
Mod File Uploads: 5
Mod Description:  Give your users awards.

Installation

1. Upload Files
/root/myawards.php
/root/inc/plugins/my_awards.php
/root/inc/languages/english/myawards.lang.php
/root/inc/languages/english/admin/user_myawards.lang.php
/root/admin/modules/user/myawards.php

2. Create folder /root/uploads/awards/  (already included in download too)

3. Chmod /root/uploads/awards/ to 777.

4. Activate in admincp the plugin "My Awards".

5. You can now go into admincp under "Users & Groups" and you will see the nav menu on left for "My Awards".

6. (Optional) You may need to set permissions also for your other admins.

The rest should be self explanatory.

This plugin adds the award to the postbit (classic and horizontal).  On the profile page you will see a row for the Awards and display the amount and a link to the details page.  The awards page can also be directly linked to if you want to add a spot in your header. It's myawards.php of course.
The plugin also adds a link to the awards page in the footer and adds the latest X granted awards on the stats.php page. Setting for how many awards to display in stats is configurable in Settings under "My Awards".

I plan to add more to this plugin but right now this is a great base.  Any suggestions or bugs found please post at Mybb Central.


==================
UPGRADE INSTRUCTIONS
==================
MyAwards 2.0
------------
These instructions are for if you are upgrading from My Awards 1.x to My Awards 2.x
-Overwrite old files with new My Awards 2.x files.
-Deactivate "My Awards" in plugin manager.
-Activate "My Awards" in plugin manager.

With that, there should be no loss of previous award data.

MyAwards 2.1
------------
If upgrading from 2.0, just overwrite old files with new files. No need to deactivate.
If upgrading from 1.x, use instructions for MyAwards 2.0 upgrade.

MyAwards 2.2
------------
Fixed uid limitation bug. Now UID for granted awards can have 8 characters (99,999,999 being highest possible UID).

MyAwards 2.3
------------
Now optional PM is sent when granting an award.

MyAwards 2.4
------------
Fixed exploits of admincp with signatures.


Demo: http://www.hackforums.net/

I can be reached at either http://www.mybbcentral.com as username LABROCCA if you have questions.

Thank you.
Jesse Labrocca

