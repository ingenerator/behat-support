# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased

## 1.0.4 (2020-10-16)

* Improve Select2v4 compatibility with other webdrivers. Primarily to allow it to be used with chrome.

## 1.0.3 (2020-09-29)

* Remove incorrect license from docblocks

## 1.0.2 (2020-09-29)

* Add BrowserResizeExtension to resize browser window, defaults to 1024x768

## 1.0.1 (2020-03-16)

* Explicitly start selenium to set the window size - fixes bug with latest mink which has
  (in a minor version) changed the previous behavior that auto-started it on
  `->getSession()`

## 1.0.0 (2019-04-03)

* Ensure support for php7.2
* Drop support for php5

## 0.2.1 (2019-01-31)

* Fix bug where SaveFailingPagesListener threw a fatal exception if asked to write a
  file with no content (e.g. because of a white-page-500 served by the app) masking the
  actual failure.

## 0.2.0 (2018-09-18)

* Fix session-start detection for SaveFailingPages listener with a non-browserkit 
  session : was incorrectly reporting session not started when it should be started.
* Add PrintDebugTrait as a little BC shorthand for printing stuff from behat 
  contexts.
* Replace PhantomJSControllerContext with PhantomJSControllerExtension - does
  the same job, but as an extension.
* Add a set of useful extensions for bootstrapping, saving failing pages,
  providing kohana application dependencies into contexts that need them and 
  co-ordinating and injecting test data factories and repositories (which need
  to be actually implemented separately in the project for now). 
* Require Behat version 3

## 0.1.0 (2018-04-25)

* Add an element wrapper for the v4.x version of select2/select2 - see Select2v4
* Update JSEventWaiter to support `window` and `document` as element selectors
* Add MinkResourceDownloader
* Add JSEventWaiter to watch for javascript events
* Add a PhantomJSControllerContext for automatically starting / stopping phantomjs when required
* Add Spin::fn for retrying assertions
* Add DateParam::parse for parsing relative date strings from features
* Project commenced
