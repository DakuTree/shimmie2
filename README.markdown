```
     _________.__     .__                   .__         ________   
    /   _____/|  |__  |__|  _____    _____  |__|  ____  \_____  \  
    \_____  \ |  |  \ |  | /     \  /     \ |  |_/ __ \  /  ____/  
    /        \|   Y  \|  ||  Y Y  \|  Y Y  \|  |\  ___/ /       \  
   /_______  /|___|  /|__||__|_|  /|__|_|  /|__| \___  >\_______ \ 
           \/      \/           \/       \/          \/         \/ 
                                                                
```

# Shimmie

[![Build Status](https://travis-ci.org/DakuTree/shimmie2.svg?branch=FTAG)](https://travis-ci.org/DakuTree/shimmie2)

This is the custom/personal branch of Shimmie.

It changes a bunch of things which allow Shimmie, when used with a bunch of other scripts, to be used as something that can be used for personal manga/comic management.

This branch is only here as an example of how this can be done, and isn't really meant to be used by someone other than myself.
If you are however, curious on how to get this thing working, hit my email @ <mailto:admin+ftag@codeanimu.net>

(If you somehow got here by accident, and are looking for the official shimmie repo, please check shish/shimmie2)

FTAG RESET BRANCH (incase):
	git cherry-pick 7d771e17ee0a0505ec3acf64219ce1c6bcc55b96
	git cherry-pick e65e9ff4c89bc0f699efd908ef47b1b7065a378d
	git merge ext-image-history
	git merge _patch-theme-custom
	git merge idea-autocomplete-tagit
	<MANUAL> git merge old-patch-filename
	git merge patch-ext
	<MANUAL> git merge FTAG-OLD

# Licence

All code is released under the [GNU GPL Version 2](http://www.gnu.org/licenses/gpl-2.0.html) unless mentioned otherwise.

If you give shimmie to someone else, you have to give them the source (which should be easy, as PHP
is an interpreted language...). If you want to add customisations to your own
site, then those customisations belong to you, and you can do what you want
with them.
