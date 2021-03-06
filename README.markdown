# Far Future Cache Controller
 
* Version: 0.1  
* Author: [Nick Dunn](http://nick-dunn.co.uk)  
* Build Date: 19 July 2010  
* Requirements: Symphony 2.0.6+

**WARNING: this is an experimental prototype**

## Installation
 
1. Upload the 'far_future_cache_controller' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "Far Future Cache Controller", choose Enable from the with-selected menu, then click Apply.
3. Use the $cache-rev parameter when building your URLs to assets within `/workspace`


## Usage

If you're a conscientious developer you're probably already studying for your YSlow! A-grade. One metric is to set far-futures expiry headers on your static assets. Doing so ensures browsers keep your assets cached for longer. Maybe you're using something like this:

	<IfModule mod_expires.c>
		ExpiresActive On
		ExpiresDefault "access plus 6 months"
	</IfModule>

Browsers won't even bother making a trip to the server to see whether a newer version is available... which is both a blessing and a curse. On the one hand you reduce server overhead and bandwidth. On the other, if your assets change then how do you force clients to get the latest version?

The only sound way to achieve this is to change the asset file name itself. The the most part this is completely impractical given the number of assets that may change between each release. If you're deploying your website directly from git then each time you make a deployment the latest HEAD value will change; a value that is ideal for creating unique URLs.

This extension provides a single parameter in the Param Pool: `$cache-controller`. This will be a hash string which you should use to build URLs to assets served from your Symphony `/workspace` folder. Build URLs in the following way:

	/workspace/{$cache-controller}/...

So if your existing URL to a file is:

	/workspace/assets/css/master.css

Then the new URL should be built as:

	/workspace/{$cache-controller}/assets/css/master.css

When the page loads this URL will end up looking something like:

	/workspace/7f9057226e4736faf7a0d8de7d5bfbaed580dfe0/assets/css/master.css

The next time you pull an update from your origin git repository this hash will change, and clients will be forced to download fresh assets.

### What if I don't use git?

Fear not, you can still use this extension without git. If you prefer you can create a file in `/manifest` named `cache_controller` and change its value each time you want to bust the cache.

	/manifest/cache_controller

The value of this file will be hashed and used instead. So you can change its value to an SVN revision number, a timestamp, or the name of your favourite Teletubby.

If that's too much work, then don't create that file at all. This extension will look at the last modified dates of all assets in your workspace (CSS, JS and images) and will hash the date of the newest file. So every time you deploy a new asset, your cache will expire. Sweet! Note however that this is not the most performant of the above methods, since every page load will recurse the file structure of your workspace directory looking for assets and their last modified times.