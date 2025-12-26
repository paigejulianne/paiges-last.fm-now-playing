=== Paige's Last.FM Now Playing ===
Contributors: paigejulianne
Tags: lastfm, music, now playing, widget, block
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display your recently played tracks from Last.fm with a beautiful Spotify-inspired design. Includes a Gutenberg block, classic widget, and shortcode.

== Description ==

**Paige's Last.FM Now Playing** lets you showcase your music listening history on your WordPress site with a sleek, Spotify-inspired design. Display your recently scrobbled tracks anywhere on your site using the Gutenberg block editor, classic widgets, or shortcodes.

= Features =

* **Gutenberg Block** - Add your recent tracks to any post or page with full block editor support
* **Classic Widget** - Perfect for sidebars and widget areas
* **Shortcode Support** - Use `[lastfm_now_playing]` anywhere shortcodes work
* **Spotify-Inspired Themes** - Choose from Light, Dark, or Transparent themes
* **Now Playing Indicator** - Animated indicator shows when you're currently listening
* **User Profile Header** - Displays your Last.fm avatar, username, and profile link
* **Configurable Display** - Control the number of tracks, show/hide album names and durations
* **Per-Instance Settings** - Override defaults for each block or widget placement
* **Responsive Design** - Looks great on all screen sizes
* **Caching** - Built-in caching reduces API calls and improves performance
* **Translation Ready** - Fully internationalized and ready for translation

= What's Displayed =

Each track listing includes:

* Album artwork (scaled)
* Song title
* Artist name
* Album name (optional)
* Track duration (optional)
* "Now Playing" status or time since played

= Requirements =

* A Last.fm account
* A Last.fm API key (free from [Last.fm API](https://www.last.fm/api/account/create))
* WordPress 5.8 or higher
* PHP 7.4 or higher

= Privacy =

This plugin connects to the Last.fm API to fetch your listening data. No personal data is sent to Last.fm beyond your username and API key for authentication. The plugin caches data locally to minimize API requests. Please review [Last.fm's Privacy Policy](https://www.last.fm/legal/privacy) for information about how Last.fm handles your data.

== Installation ==

1. Upload the `lastfm-now-playing` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Last.fm Now Playing to configure your API credentials
4. Add the block, widget, or shortcode to your site

= Getting Your API Key =

1. Go to [Last.fm API Account Creation](https://www.last.fm/api/account/create)
2. Sign in with your Last.fm account
3. Fill in an application name and description
4. Copy the API Key provided
5. Paste it into the plugin settings

== Frequently Asked Questions ==

= Where do I get a Last.fm API key? =

Visit [https://www.last.fm/api/account/create](https://www.last.fm/api/account/create), sign in with your Last.fm account, and create an application. The API key will be provided immediately.

= How do I add the block to a page? =

In the block editor, click the + button to add a new block and search for "Last.fm Now Playing". Click to insert it, then configure the settings in the block sidebar.

= Can I use different settings for different blocks? =

Yes! Each block and widget instance can have its own settings for number of tracks, theme, and display options. These override the defaults set in the plugin settings.

= How often is the data updated? =

By default, the plugin caches data for 5 minutes (300 seconds). You can adjust this in the plugin settings between 1 minute and 1 hour. If a track is currently playing, the cache time is reduced to ensure the "Now Playing" status stays current.

= Why isn't the track duration showing? =

Track duration requires an additional API call per track, which may slow down the display slightly. Also, some tracks in Last.fm's database don't have duration information available.

= Can I style the display with my own CSS? =

Yes! The plugin uses BEM-style class names that you can target with custom CSS. The main container has the class `lastfm-np-container` and theme classes like `lastfm-np-theme-dark`.

= Does this work with page builders? =

Yes! You can use the shortcode `[lastfm_now_playing]` in most page builders. The Gutenberg block also works in Full Site Editing themes.

== Screenshots ==

1. Dark theme display showing recent tracks with album artwork
2. Light theme variation
3. Transparent theme for overlay on custom backgrounds
4. Block settings in the Gutenberg editor
5. Widget configuration in the customizer
6. Plugin settings page

== Changelog ==

= 1.0.0 =
* Initial release
* Gutenberg block with per-instance configuration
* Classic widget support
* Shortcode support
* Three Spotify-inspired themes: Light, Dark, Transparent
* User profile header with avatar
* Now Playing animation indicator
* Configurable track count, album display, and duration display
* Built-in caching with configurable duration
* Fully internationalized

== Upgrade Notice ==

= 1.0.0 =
Initial release of Paige's Last.FM Now Playing.

== Shortcode Usage ==

Basic usage:
`[lastfm_now_playing]`

With all parameters:
`[lastfm_now_playing count="5" theme="dark" show_album="true" show_duration="true"]`

= Parameters =

* **count** - Number of tracks to display (1-50, default: 5)
* **theme** - Theme style: "light", "dark", or "transparent" (default: from settings)
* **show_album** - Show album name: "true" or "false" (default: from settings)
* **show_duration** - Show track duration: "true" or "false" (default: from settings)
