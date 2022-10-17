# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased

## 1.2.0 (2022-10-17)

* Support PHP 8.1 and PHP 8.2

## 1.1.3 (2021-10-11)

* Add a custom exception filter to the `Spin::fn()` to simplify retrying certain types of exceptions but not others.

## 1.1.2 (2021-01-06)

* Catch driver-level errors when attempting to capture failing page HTML / screenshots.
  If the listener throws an exception, it terminates the whole behat process. This can happen if
  the browser has crashed / lost connection, and prevents e.g. the progress formatter from rendering
  the list of failed steps. Instead, print the exception details to output and move on.

## 1.1.1 (2021-05-10)

* Improve reliability of select2 filling wrapping it in a retry loop at 5ms intervals up to 10 times.

## 1.1.0 (2021-04-21)

* Support PHP8
* Drop support for PHP7.2

## 1.0.7 (2020-10-29)

* Switch to github actions instead of travis for testing
* Add `MinkBrowserChecker::requireChromeDriver` and `::requireGoutteDriver` to enforce explicit driver types.
* Remove unnecessary / incorrect `return` statement from javascript commands

## 1.0.6 (2020-10-28)

* FIX HashTableNode named constructor ::withRows()

## 1.0.5 (2020-10-16)

* Add HashNodeTable to build TableNode's from arrays

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
